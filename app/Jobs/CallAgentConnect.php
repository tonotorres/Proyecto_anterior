<?php

namespace App\Jobs;

use App\CurrentCall;
use App\CurrentCallLog;
use App\CurrentCallUser;
use App\Events\CallUpdate;
use App\Events\UpdateUserStatus;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CallAgentConnect implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $payload;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if(!empty($this->payload['company_id']) && !empty($this->payload['linkedid']) && !empty($this->payload['extension']) && !empty($this->payload['start'])) {
            //RC: Miramos si tenemos la llamada
            $current_call = CurrentCall::where('linkedid', $this->payload['linkedid'])
                ->first();

            if(!empty($current_call)) {
                if($current_call->call_type_id == 2 && $current_call->to === $this->payload['extension']) {
                    exit;
                }
                
                //RC: Miramos si tenemos un usuario con esta extensión
                $user = User::where('extension', $this->payload['extension'])
                    ->where('company_id', $this->payload['company_id'])
                    ->first();

                //RC: Miramos si el usuario tiene departamento lo tenemos que añadir
                if(!empty($user->department_id)) {
                    $current_call->department_id = $user->department_id;
                } else {
                    $current_call->department_id = null;
                }

                //RC: Miramos si tenemos la lista de espera
                if(empty($current_call->duration_wait)) {
                    $current_call->duration_wait = strtotime($this->payload['start']) - $current_call->start;
                }

                $current_call->call_status_id = 2;
                $current_call->save();

                //RC: Miramos si tenemos algun usuario activo en la llamada
                $old_current_call_user = CurrentCallUser::where('current_call_id', $current_call->id)
                    ->whereNull('duration')
                    ->first();

                if(!empty($old_current_call_user)) {
                    if($old_current_call_user->extension == $this->payload['extension']) {
                        exit;
                    }
                    $old_current_call_user->duration = strtotime($this->payload['start']) - $old_current_call_user->start;
                    $old_current_call_user->save();

                    if(!empty($old_current_call_user->user_id)) {
                        $old_user = User::findOrFail($old_current_call_user->user_id);
                        broadcast(new UpdateUserStatus($old_user));
                    }
                }

                //RC: Guardamos el usuario nuevo
                $data['current_call_id'] = $current_call->id;
                if(!empty($user)) {
                    $data['user_id'] = $user->id;
                    if(!empty($user->department_id)) {
                        $data['department_id'] = $user->department_id;
                    } else {
                        $data['department_id'] = null;
                    }
                }
                $data['extension'] = $this->payload['extension'];
                $data['start'] = strtotime($this->payload['start']);
                CurrentCallUser::create($data);

                if(!empty($user)) {
                    broadcast(new UpdateUserStatus($user));
                }

                //RC: Generamos el registro del log
                $data_log['current_call_id'] = $current_call->id;
                $data_log['call_log_type_id'] = 6;
                $data_log['description'] = 'Conectamos con '.(!empty($user) ? $user->name.' ' : '').'('.$this->payload['extension'].')';
                $data_log['start'] = strtotime($this->payload['start']);
                CurrentCallLog::create($data_log);

                //RC: Emitimos el evento de update call
                $call_stat['id'] = $current_call->id;
                $call_stat['from'] = $current_call->from;
                $call_stat['to'] = $current_call->to;
                $call_stat['start'] = $current_call->start;
                $call_stat['duration'] = strtotime('now') - $current_call->start;
                if(!empty($user)) {
                    $call_stat['user_id'] = $user->id;
                    $call_stat['user_name'] = $user->name;
                    $call_stat['extension'] = $this->payload['extension'];
                    if(!empty($user->department_id)) {
                        $call_stat['department_id'] = $user->department_id;
                        $current_call->department_id = $user->department_id;
                        $current_call->save();
                    } else {
                        $call_stat['department_id'] = null;
                    }
                } else {
                    $call_stat['user_id'] = null;
                    $call_stat['department_id'] = $current_call->department_id;;
                    $call_stat['user_name'] = $this->payload['extension'];
                    $call_stat['extension'] = $this->payload['extension'];
                }
                $call_stat['queue'] = $current_call->queue;
                $call_stat['call_type_id'] = $current_call->call_type_id;
                $call_stat['call_status_id'] = $current_call->call_status_id;

                broadcast(new CallUpdate($call_stat, $current_call, $current_call->company_id));

            }
        }
    }
}
