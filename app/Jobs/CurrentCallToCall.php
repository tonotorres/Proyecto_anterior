<?php

namespace App\Jobs;

use App\Call;
use App\CallComment;
use App\CallIvr;
use App\CallLog;
use App\CallQueue;
use App\CallUser;
use App\CallUserAdministrativeTime;
use App\CallUserCalled;
use App\CurrentCall;
use App\Events\CurrentCallToCallEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CurrentCallToCall implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $current_call_id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($current_call_id)
    {
        $this->current_call_id = $current_call_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $current_call = CurrentCall::where('id', $this->current_call_id)
            ->first();

        if(!empty($current_call)) {
            //RC: Copiamos la llamada
            $call = new Call();
            $call->company_id = $current_call->company_id;
            $call->call_type_id = $current_call->call_type_id;
            $call->call_status_id = $current_call->call_status_id;
            $call->call_end_id = $current_call->call_end_id;
            $call->route_in_id = $current_call->route_in_id;
            $call->route_out_id = $current_call->route_out_id;
            $call->department_id = $current_call->department_id;
            $call->account_id = $current_call->account_id;
            $call->campaign_id = $current_call->campaign_id;
            $call->campaign_contact_id = $current_call->campaign_contact_id;
            $call->from = $current_call->from;
            $call->to = $current_call->to;
            $call->queue = $current_call->queue;
            $call->uniqueid = $current_call->uniqueid;
            $call->linkedid = $current_call->linkedid;
            $call->start = $current_call->start;
            $call->duration_wait = $current_call->duration_wait;
            $call->duration = $current_call->duration;
            $call->save();

            //RC: Copiamos los comentarios
            if ($current_call->call_comments()->count() > 0) {
                foreach($current_call->call_comments as $current_call_comment) {
                    $call_comment = new CallComment();
                    $call_comment->call_id = $call->id;
                    $call_comment->user_id = $current_call_comment->user_id;
                    $call_comment->comment = $current_call_comment->comment;
                    $call_comment->created_at = $current_call_comment->created_at;
                    $call_comment->save();
                }
            }

            //RC: Copiamos los ivr
            if ($current_call->call_ivrs()->count() > 0
            ) {
                foreach($current_call->call_ivrs as $current_call_ivr) {
                    $call_ivr = new CallIvr();
                    $call_ivr->call_id = $call->id;
                    $call_ivr->pbx_ivr = $current_call_ivr->pbx_ivr;
                    $call_ivr->option = $current_call_ivr->option;
                    $call_ivr->start = $current_call_ivr->start;
                    $call_ivr->save();
                }
            }

            //RC: Copiamos las colas
            if (
                $current_call->call_queues()->count() > 0
            ) {
                foreach ($current_call->call_queues as $current_call_queue) {
                    $call_queue = new CallQueue();
                    $call_queue->call_id = $call->id;
                    $call_queue->queue = $current_call_queue->queue;
                    $call_queue->start = $current_call_queue->start;
                    $call_queue->save();
                }
            }

            //RC: Copiamos los usuarios
            if (
                $current_call->call_users()->count() > 0
            ) {
                foreach($current_call->call_users as $current_call_user) {
                    $call_user = new CallUser();
                    $call_user->call_id = $call->id;
                    $call_user->user_id = $current_call_user->user_id;
                    $call_user->department_id = $current_call_user->department_id;
                    $call_user->extension = $current_call_user->extension;
                    $call_user->start = $current_call_user->start;
                    $call_user->duration = $current_call_user->duration;
                    $call_user->save();
                }
            }

            //RC: Copiamos los usuarios llamados
            if ($current_call->call_user_calleds()->count() > 0) {
                foreach($current_call->call_user_calleds as $current_call_user_calleds) {
                    $call_user_called = new CallUserCalled();
                    $call_user_called->call_id = $call->id;
                    $call_user_called->user_id = $current_call_user_calleds->user_id;
                    $call_user_called->department_id = $current_call_user_calleds->department_id;
                    $call_user_called->extension = $current_call_user_calleds->extension;
                    $call_user_called->start = $current_call_user_calleds->start;
                    $call_user_called->answered = $current_call_user_calleds->answered;
                    $call_user_called->save();
                }
            }

            $callUserAdministrativeTime = CallUserAdministrativeTime::where('call_type', 'current_call')
            ->where('call_id', $current_call->id)
            ->first();

            if (!empty($callUserAdministrativeTime)) {
                $callUserAdministrativeTime->call_type = 'call';
                $callUserAdministrativeTime->call_id = $call->id;
                $callUserAdministrativeTime->save();
            }

            broadcast(new CurrentCallToCallEvent($current_call, $call));

            //DB::insert('INSERT INTO call_current_call VALUES ('.$call->id.','.$current_call->id.')');
        
            $current_call->delete();
        }
    }
}
