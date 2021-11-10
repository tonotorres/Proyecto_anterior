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

class EndBreakTimeUser implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $break_time_user_report;
    public $user_status;
    public $user_id;
    private $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($break_time_user_report, $user)
    {
        $this->break_time_user_report = $break_time_user_report;
        $this->user_id = $user->id;
        $this->user = $user;
        $this->user_status = get_user_status($user);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('App.Company.'.$this->user->company_id);
    }
}
