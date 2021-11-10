<?php

namespace App\Jobs;

use App\CurrentCall;
use App\CurrentCallUser;
use App\CurrentCallUserCalled;
use App\Events\CallUpdate;
use App\Events\UpdateUserStatus;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PbxAgentConnect implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //RC: Obtenemos la llamada
        $current_call = getCurrentCallByLinkedid($this->data['Linkedid'], $this->data['company_id']);
        
        if (!empty($current_call)) {
            $this->data['company_id'] = $current_call->company_id;
        }

        if (!empty($current_call)) {
            $this->data['extension'] = get_extension_interface($this->data['Interface']);
            if ($current_call->call_type_id == 2 && $current_call->to === $this->data['extension']) {
                exit;
            }

            //RC: Miramos si tenemos un usuario con esta extensión
            $user = get_extension_user($this->data['company_id'], $this->data['extension']);

            //RC: Miramos si el usuario tiene departamento lo tenemos que añadir
            if (!empty($user->department_id)) {
                $current_call->department_id = $user->department_id;
            } else {
                $current_call->department_id = null;
            }

            //RC: Miramos si tenemos la lista de espera
            if (empty($current_call->duration_wait)) {
                $current_call->duration_wait = $this->data['start'] - $current_call->start;
            }

            $current_call->call_status_id = 2;
            $current_call->save();

            //RC: Miramos si tenemos algun usuario activo en la llamada
            $old_current_call_user = CurrentCallUser::where('current_call_id', $current_call->id)
                ->whereNull('duration')
                ->first();

            if (!empty($old_current_call_user)) {
                if ($old_current_call_user->extension != $this->data['extension']) {
                    $old_current_call_user->duration = $this->data['start'] - $old_current_call_user->start;
                    $old_current_call_user->save();

                    if (!empty($old_current_call_user->user_id)) {
                        $old_user = User::where('id', $old_current_call_user->user_id)->first();
                        if (!empty($old_user)) {
                            broadcast(new UpdateUserStatus($old_user));
                        }
                    }
                }
            }

            //RC: Guardamos el usuario nuevo
            $this->data_save['current_call_id'] = $current_call->id;
            if (!empty($user)) {
                $this->data_save['user_id'] = $user->id;
                if (!empty($user->department_id)) {
                    $this->data_save['department_id'] = $user->department_id;
                } else {
                    $this->data_save['department_id'] = null;
                }
            }
            $this->data_save['extension'] = $this->data['extension'];
            $this->data_save['start'] = $this->data['start'];
            CurrentCallUser::create($this->data_save);

            //RC: marcamos en los usuarios de la tabla user_calles como respondida
            if (!empty($this->data_save['user_id'])) {
                $current_call_user_calleds = CurrentCallUserCalled::where('current_call_id', $current_call->id)
                    ->where('user_id', $this->data_save['user_id'])
                    ->orderBy('start', 'desc')
                    ->first();

                if (!empty($current_call_user_calleds)) {
                    $current_call_user_calleds->answered = 1;
                    $current_call_user_calleds->save();
                }
            } else {
                //RC: Si no tenemos usuario lo miramos por la extensión
                $current_call_user_calleds = CurrentCallUserCalled::where('current_call_id', $current_call->id)
                    ->where('extension', $this->data_save['extension'])
                    ->orderBy('start', 'desc')
                    ->first();

                if (!empty($current_call_user_calleds)) {
                    $current_call_user_calleds->answered = 1;
                    $current_call_user_calleds->save();
                }
            }

            if (!empty($user)) {
                broadcast(new UpdateUserStatus($user));
            }

            //RC: Emitimos el evento de update call
            $call_stat['id'] = $current_call->id;
            $call_stat['from'] = $current_call->from;
            $call_stat['to'] = $current_call->to;
            $call_stat['start'] = $current_call->start;
            $call_stat['duration'] = strtotime('now') - $current_call->start;
            if (!empty($user)) {
                $call_stat['user_id'] = $user->id;
                $call_stat['user_name'] = $user->name;
                $call_stat['extension'] = $this->data['extension'];
                if (!empty($user->department_id)) {
                    $call_stat['department_id'] = $user->department_id;
                    $current_call->department_id = $user->department_id;
                    $current_call->save();
                } else {
                    $call_stat['department_id'] = null;
                }
            } else {
                $call_stat['user_id'] = null;
                $call_stat['department_id'] = $current_call->department_id;;
                $call_stat['user_name'] = $this->data['extension'];
                $call_stat['extension'] = $this->data['extension'];
            }
            $call_stat['queue'] = $current_call->queue;
            $call_stat['call_type_id'] = $current_call->call_type_id;
            $call_stat['call_status_id'] = $current_call->call_status_id;
            $call_stat['event'] = 'agent_connect';

            broadcast(new CallUpdate($call_stat, $current_call, $current_call->company_id));
        }
    }
}
