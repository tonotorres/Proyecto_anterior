<?php

namespace App\Jobs;

use App\PbxBridge;
use App\PbxChannel;
use App\PbxChannelState;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PbxBridgeLeave implements ShouldQueue
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
        $pbx_channel = PbxChannel::where('name', $this->data['Channel'])->first();
        if (!empty($pbx_channel)) {
            $bridge_id = $pbx_channel->pbx_bridge_id;
            $pbx_channel_state = PbxChannelState::where('key', $this->data['ChannelState'])->first();

            if (empty($pbx_channel_state)) {
                $pbx_channel_state = new PbxChannelState();
                $pbx_channel_state->key = $this->data['ChannelState'];
                $pbx_channel_state->name = $this->data['ChannelStateDesc'];
                $pbx_channel_state->save();
            }

            $pbx_channel->pbx_channel_state_id = $pbx_channel_state->id;
            $pbx_channel->pbx_bridge_id = null;
            $pbx_channel->save();
        }

        if (!empty($bridge_id)) {
            if (PbxChannel::where('pbx_bridge_id', $bridge_id)->count() == 0) {
                $pbx_bridge = PbxBridge::where('name', $bridge_id)->delete();
            }
        }
    }
}
