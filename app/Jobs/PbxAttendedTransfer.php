<?php

namespace App\Jobs;

use App\CurrentCall;
use App\CurrentCallUser;
use App\Events\CallUpdate;
use App\PbxChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PbxAttendedTransfer implements ShouldQueue
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
        //RC: Obtenemos la extensión des del canal
        $originExtension = get_channel_name($this->data['OrigTransfererChannel']);

        //RC Obtenemos la llamada actual
        $currentCall = getCurrentCallByLinkedid($this->data['TransferTargetLinkedid'], $this->data['company_id']);

        if (!empty($currentCall)) {
            //RC: obtenemos el usuario activo de la llamada
            // $currentCallUserOld = finishCurrentCallUserByExtension($currentCall, $originExtension, $this->data['start']);
            $pbxChannel = PbxChannel::where('name', $this->data['TransferTargetChannel'])->first();
            if(!empty($pbxChannel)) {
                $oldCurrentCall = CurrentCall::where('id', $pbxChannel->current_call_id)->first();

                $pbxChannel->current_call_id = $currentCall->id;
                $pbxChannel->save();

                if (PbxChannel::where('current_call_id', $oldCurrentCall->id)->count() == 0) {
                    if ($oldCurrentCall->call_users()->count() > 0
                    ) {
                        $oldCurrentCall->call_status_id = 3;
                    } else if ($currentCall->call_status_id != 5) {
                        $oldCurrentCall->call_status_id = 4;
                    }
    
                    $oldCurrentCall->duration = $this->data['start'] - $oldCurrentCall->start;
                    $oldCurrentCall->save();
    
                    //RC: Emitimos el evento de update call
                    $call_stat['id'] = $oldCurrentCall->id;
                    $call_stat['from'] = $oldCurrentCall->from;
                    $call_stat['to'] = $oldCurrentCall->to;
                    $call_stat['start'] = $oldCurrentCall->start;
                    $call_stat['duration'] = strtotime('now') - $oldCurrentCall->start;
                    $call_stat['user_id'] = null;
                    $call_stat['user_name'] = null;
                    $call_stat['department_id'] = $oldCurrentCall->department_id;;
                    $call_stat['extension'] = null;
                    $call_stat['queue'] = null;
                    $call_stat['call_type_id'] = $oldCurrentCall->call_type_id;
                    $call_stat['call_status_id'] = $oldCurrentCall->call_status_id;
    
                    broadcast(new CallHangup($call_stat, $oldCurrentCall, $oldCurrentCall->company_id));
    
                    CurrentCallToCall::dispatch($oldCurrentCall->id)->delay('5');
                }

                
            }

            //RC: asignamos el usuario que recibe la llamada
            $currentCallUser = startCurrentCallUser($currentCall, $this->data['OrigTransfererConnectedLineNum'], $this->data['start']);

            //RC: Emitimos el evento de update call
            $callStat['id'] = $currentCall->id;
            $callStat['from'] = $currentCall->from;
            $callStat['to'] = $currentCall->to;
            $callStat['start'] = $currentCall->start;
            $callStat['duration'] = strtotime('now') - $currentCall->start;
            $callStat['extension'] = $currentCallUser->extension;
            $callStat['queue'] = $currentCall->queue;
            $callStat['call_type_id'] = $currentCall->call_type_id;
            $callStat['call_status_id'] = $currentCall->call_status_id;
            $callStat['event'] = 'agent_connect';

            if (!empty($currentCallUser->user_id)) {
                $callStat['user_id'] = $currentCallUser->user_id;
                $callStat['user_name'] = $currentCallUser->user->name;
            } else {
                $callStat['user_id'] = null;
                $callStat['user_name'] = null;
            }

            broadcast(new CallUpdate($callStat, $currentCall, $currentCall->company_id));
        }
    }
}
