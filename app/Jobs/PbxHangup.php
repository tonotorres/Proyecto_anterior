<?php

namespace App\Jobs;

use App\CurrentCall;
use App\CurrentCallUser;
use App\Events\CallHangup;
use App\Events\CallUpdate;
use App\Events\UpdateUserStatus;
use App\Extension;
use App\PbxChannel;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PbxHangup implements ShouldQueue
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
        //RC: Eliminamos el canal si no existe
        if (!empty($this->data['Channel'])) {
            finishChannel($this->data['Channel']);
        }

        //RC: Obtenemos la llamada
        $currentCall = getCurrentCallByLinkedid($this->data['Linkedid'], $this->data['company_id']);

        if (!empty($currentCall)) {
            //RC: Obtenemos la extensión por el canal
            $extension = get_channel_name($this->data['Channel']);

            //RC: Miramos si tenemos que finalizar una extensión
            finishCurrentCallUserByExtension($currentCall, $extension, $this->data['start']);

            //RC: Miramos si tenemos más canales asignados a la llamada
            if (PbxChannel::where('current_call_id', $currentCall->id)->count() == 0) {
                if ($currentCall->call_users()->count() > 0
                ) {
                    $currentCall->call_status_id = 3;
                } else if ($currentCall->call_status_id != 5) {
                    $currentCall->call_status_id = 4;
                }

                $currentCall->duration = $this->data['start'] - $currentCall->start;
                $currentCall->save();

                //RC: Emitimos el evento de update call
                $call_stat['id'] = $currentCall->id;
                $call_stat['from'] = $currentCall->from;
                $call_stat['to'] = $currentCall->to;
                $call_stat['start'] = $currentCall->start;
                $call_stat['duration'] = strtotime('now') - $currentCall->start;
                $call_stat['user_id'] = null;
                $call_stat['user_name'] = null;
                $call_stat['department_id'] = $currentCall->department_id;;
                $call_stat['extension'] = null;
                $call_stat['queue'] = null;
                $call_stat['call_type_id'] = $currentCall->call_type_id;
                $call_stat['call_status_id'] = $currentCall->call_status_id;

                broadcast(new CallHangup($call_stat, $currentCall, $currentCall->company_id));

                CurrentCallToCall::dispatch($currentCall->id)->delay('5');
            } else {
                //RC: Si tenemos canales mandamos un evento actualización de llamada
                //RC: Emitimos el evento de update call
                $call_stat['id'] = $currentCall->id;
                $call_stat['from'] = $currentCall->from;
                $call_stat['to'] = $currentCall->to;
                $call_stat['start'] = $currentCall->start;
                $call_stat['duration'] = strtotime('now') - $currentCall->start;
                $call_stat['user_id'] = null;
                $call_stat['department_id'] = $currentCall->department_id;;
                $call_stat['user_name'] = '';
                $call_stat['extension'] = '';
                $call_stat['queue'] = $currentCall->queue;
                $call_stat['call_type_id'] = $currentCall->call_type_id;
                $call_stat['call_status_id'] = $currentCall->call_status_id;
                $call_stat['event'] = 'agent_connect';

                broadcast(new CallUpdate($call_stat, $currentCall, $currentCall->company_id));
            }
        }
        


        /*if (!empty($this->data['company_id']) && !empty($this->data['Linkedid']) && !empty($this->data['start'])) {
            //RC: Miramos si tenemos la llamada
            $current_call = CurrentCall::where('linkedid', $this->data['Linkedid'])
                ->first();

            if (!empty($current_call)) {
                //RC: Miramos si tenemos algun canal con esta llamada
                if (PbxChannel::where('current_call_id', $current_call->id)->count() == 0) {
                    //RC: Si no tenemos más canales tenemos que finalizar la llamada

                    //RC: Miramos si tenemos algun usuario activo en la llamada
                    $old_current_call_user = CurrentCallUser::where('current_call_id', $current_call->id)
                        ->whereNull('duration')
                        ->first();
                    if (!empty($old_current_call_user)) {
                        $old_current_call_user->duration = $this->data['start'] - $old_current_call_user->start;
                        $old_current_call_user->save();
                        $current_call->call_status_id = 3;

                        if (!empty($old_current_call_user->user_id)) {
                            $old_user = User::where('id', $old_current_call_user->user_id)->first();
                            if (!empty($old_user)) {
                                broadcast(new UpdateUserStatus($old_user));
                            }
                        }
                    } else {
                        $old_current_call_user = CurrentCallUser::where('current_call_id', $current_call->id)
                            ->first();

                        if (!empty($old_current_call_user)) {
                            $current_call->call_status_id = 3;
                        } else  if ($current_call->call_status_id != 5) {
                            $current_call->call_status_id = 4;
                        }
                    }

                    $current_call->duration = $this->data['start'] - $current_call->start;
                    $current_call->save();

                    //RC: Emitimos el evento de update call
                    $call_stat['id'] = $current_call->id;
                    $call_stat['from'] = $current_call->from;
                    $call_stat['to'] = $current_call->to;
                    $call_stat['start'] = $current_call->start;
                    $call_stat['duration'] = strtotime('now') - $current_call->start;
                    $call_stat['user_id'] = null;
                    $call_stat['user_name'] = null;
                    $call_stat['department_id'] = $current_call->department_id;;
                    $call_stat['extension'] = null;
                    $call_stat['queue'] = null;
                    $call_stat['call_type_id'] = $current_call->call_type_id;
                    $call_stat['call_status_id'] = $current_call->call_status_id;

                    broadcast(new CallHangup($call_stat, $current_call, $current_call->company_id));

                    CurrentCallToCall::dispatch($current_call->id)->delay('5');
                } else {
                    $channel_prefix = get_channel_prefix($this->data['Channel']);
                    $channel_exten_name = get_channel_name($this->data['Channel']);
                    $extension = Extension::where('company_id', $current_call->company_id)->where('number', $channel_exten_name)->first();
                    if (($channel_prefix == 'PJSIP' || $channel_prefix == 'SIP') && !empty($extension)) {
                        $current_call_user = $current_call->call_users()->where('extension', $extension->number)->whereNull('duration')->first();

                        if (!empty($current_call_user)) {
                            $current_call_user->duration = $this->data['start'] - $current_call_user->start;
                            $current_call_user->save();

                            if (!empty($current_call_user)) {
                                $old_user = User::where('id', $current_call_user->user_id)->first();
                                if (!empty($old_user)) {
                                    broadcast(new UpdateUserStatus($old_user));
                                }
                            }

                            $call_event = 'CallUpdate';
                            generate_event_call_start($current_call, $call_event);
                        }
                    }
                }
            }
        }*/
    }
}
