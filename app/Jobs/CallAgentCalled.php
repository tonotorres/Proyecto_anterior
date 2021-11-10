<?php

namespace App\Jobs;

use App\CurrentCall;
use App\CurrentCallLog;
use App\CurrentCallUserCalled;
use App\Events\CallUpdate;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CallAgentCalled implements ShouldQueue
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
                //RC: Miramos si tenemos un usuario con esta extensión
                $user = User::where('extension', $this->payload['extension'])
                    ->where('company_id', $this->payload['company_id'])
                    ->first();
                
                //RC: Generamos el registro
                $data['current_call_id'] = $current_call->id;
                if(!empty($user)) {
                    $data['user_id'] = $user->id;
                    if(!empty($user->department_id)) {
                        $data['department_id'] = $user->department_id;
                        $current_call->department_id = $user->department_id;
                        $current_call->save();
                    }
                }
                $data['extension'] = $this->payload['extension'];
                $data['start'] = strtotime($this->payload['start']);
                CurrentCallUserCalled::create($data);

                //RC: Generamos el registro del log
                $data_log['current_call_id'] = $current_call->id;
                $data_log['call_log_type_id'] = 5;
                $data_log['description'] = 'Suena la extensión '.(!empty($user) ? $user->name.' ' : '').'('.$this->payload['extension'].')';
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
                    if(!empty($user->department_id)) {
                        $call_stat['department_id'] = $user->department_id;
                    } else {
                        $call_stat['department_id'] = null;
                    }
                    $call_stat['user_name'] = $user->name;
                    $call_stat['extension'] = $this->payload['extension'];
                } else {
                    $call_stat['user_id'] = null;
                    $call_stat['department_id'] = null;
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
