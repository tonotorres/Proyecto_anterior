<?php

namespace App\Jobs;

use App\CurrentCall;
use App\CurrentCallUser;
use App\Events\UpdateUserStatus;
use App\Extension;
use App\PbxBridge;
use App\PbxChannel;
use App\PbxChannelState;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PbxBridgeEnter implements ShouldQueue
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
        //RC: Si no tenemos bridge lo tenemos que generar
        $pbx_bridge = PbxBridge::where('name', $this->data['BridgeUniqueid'])->first();

        if (empty($pbx_bridge)) {
            $pbx_bridge = new PbxBridge();
            $pbx_bridge->name = $this->data['BridgeUniqueid'];
            $pbx_bridge->save();
        }

        //RC: Si no tenemos canal lo tenemos que generar
        $pbx_channel = PbxChannel::where('name', $this->data['Channel'])->first();
        if (!empty($pbx_channel) && !empty($pbx_bridge)) {
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
            $pbx_channel->pbx_bridge_id = $pbx_bridge->id;

            if (!empty($current_call)) {
                $pbx_channel->current_call_id = $current_call->id;
                if ($current_call->to == 's') {

                    $account_contact_type = get_account_type_by_number($current_call->company_id, $this->data['CallerIDNum']);

                    if (!empty($account_contact_type)) {
                        $current_call->account_id = $account_contact_type->account_id;
                    }

                    $current_call->from = $this->data['ConnectedLineNum'];
                    $current_call->to = $this->data['CallerIDNum'];

                    $current_call->save();

                    $call_event = 'CallUpdate';
                    generate_event_call_start($current_call, $call_event, 'agent_connect');
                }
            }
            $pbx_channel->save();
        }
    }
}
