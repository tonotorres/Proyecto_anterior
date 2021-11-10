<?php

namespace App\Jobs;

use App\Call;
use App\CallUserAdministrativeTime;
use App\CurrentCall;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FinishUserAdministrativeTime implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $callUserAdministrativeTimeId;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($callUserAdministrativeTimeId)
    {
        $this->callUserAdministrativeTimeId = $callUserAdministrativeTimeId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $callUserAdministrativeTime = CallUserAdministrativeTime::where('id', $this->callUserAdministrativeTimeId)->first();

        if (!empty($callUserAdministrativeTime)) {

            if ($callUserAdministrativeTime->call_type == 'call') {
                $call = Call::where('id', $callUserAdministrativeTime->call_id)->first();
            } else {
                $call = CurrentCall::where('id', $callUserAdministrativeTime->call_id)->first();
            }

            if (!empty($call)) {
                $call_user = $call->call_users()->where('user_id', $callUserAdministrativeTime->user_id)->whereNull('administrative_time')->first();

                if (!empty($call_user)) {
                    $call_user->administrative_time = strtotime('now') - strtotime($callUserAdministrativeTime->created_at) - $call_user->duration;
                    $call_user->save();

                    $call->administrative_time = $call->administrative_time + $call_user->administrative_time;
                    $call->save();
                }
            }
            
            $user = User::where('id', $callUserAdministrativeTime->user_id)->first();

            if (!empty($user) && !empty($user->extension)) {
                $userStatus = get_user_status($user);

                if ($userStatus['status_type'] == 'online') {
                    unpause_all($user);

                    if ($user->paused_queues()->count() > 0) {
                        foreach ($user->paused_queues as $queue) {
                            pause_queue($queue->number, $user->extension);
                        }
                    }
                }
            }

            $callUserAdministrativeTime->delete();
        }
    }
}
