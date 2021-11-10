<?php

namespace App\Jobs;

use App\CurrentCall;
use App\CurrentCallUser;
use App\CurrentCallUserCalled;
use App\Events\UpdateUserStatus;
use App\PbxChannel;
use App\PbxChannelState;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PbxDialEnd implements ShouldQueue
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
        if (!empty($this->data['Channel'])) {
            $pbx_channel = PbxChannel::where('name', $this->data['Channel'])->first();

            if (!empty($pbx_channel)) {
                //RC: Si no tenemos llamada la tenemosq ue generar

                $current_call = CurrentCall::where('linkedid', $this->data['Linkedid'])
                    ->first();

                $pbx_channel_state = PbxChannelState::where('key', $this->data['ChannelState'])->first();

                if (empty($pbx_channel_state)) {
                    $pbx_channel_state = new PbxChannelState();
                    $pbx_channel_state->key = $this->data['ChannelState'];
                    $pbx_channel_state->name = $this->data['ChannelStateDesc'];
                    $pbx_channel_state->save();
                }

                $pbx_channel->pbx_channel_state_id = $pbx_channel_state->id;
                if (!empty($current_call)) {
                    $pbx_channel->current_call_id = $current_call->id;
                }
                $pbx_channel->save();

                if (!empty($current_call) && $this->data['DialStatus'] == 'ANSWER') {
                    if ($current_call->call_type_id == 2) {
                        $extension = get_channel_name($this->data['Channel']);
                    } else {
                        $extension = get_channel_name($this->data['DestChannel']);
                    }
                    $user = get_extension_user($current_call->company_id, $extension);
                    if (!empty($user)) {
                        $current_call->call_status_id = 2;
                        $current_call->department_id = $user->department_id;
                        $current_call->save();

                        $current_call_user = $current_call->call_users()->where('extension', $extension)->whereNull('duration')->first();

                        if (empty($current_call_user)) {
                            startCurrentCallUser($current_call, $user->extension, $this->data['start']);
                            /*$current_call_user = new CurrentCallUser();
                            $current_call_user->current_call_id = $current_call->id;
                            $current_call_user->start = $this->data['start'];
                            $current_call_user->extension = $user->extension;
                            $current_call_user->user_id = $user->id;
                            $current_call_user->save();*/

                            //setCurrentCallUserCalledAnsweredByExntension($current_call->id, $user->extension, 1);
                        }
                        broadcast(new UpdateUserStatus($user));


                        $call_event = 'CallUpdate';
                        generate_event_call_start($current_call, $call_event, 'agent_connect');
                    }
                }
            }
        }
    }
}
