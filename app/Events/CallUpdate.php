<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallUpdate implements ShouldBroadcastNow 
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $call_stats;
    public $current_call;
    private $company_id;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($call_stats, $current_call, $company_id)
    {
        $this->call_stats = $call_stats;
        $this->current_call = $current_call->load('call_type', 'call_status', 'department', 'account', 'campaign', 'campaign_contact', 'active_call_users', 'call_users', 'call_users.user', 'call_user_calleds', 'call_user_calleds.user', 'call_comments', 'call_comments.user', 'call_logs', 'call_end', 'call_queues', 'account.tags', 'account.account_description');
        $this->company_id = $company_id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('App.Company.'.$this->company_id);
    }
}
