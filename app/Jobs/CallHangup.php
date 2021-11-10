<?php

namespace App\Jobs;

use App\CurrentCall;
use App\CurrentCallLog;
use App\CurrentCallUser;
use App\Events\CallHangup as EventsCallHangup;
use App\Events\UpdateUserStatus;
use App\RouteIn;
use App\RouteOut;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CallHangup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $payload;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if(!empty($this->payload['company_id']) && !empty($this->payload['linkedid'])  && !empty($this->payload['uniqueid']) && !empty($this->payload['start'])) {
            //RC: Miramos si tenemos la llamada
            $current_call = CurrentCall::where('linkedid', $this->payload['linkedid'])
                ->where('uniqueid', $this->payload['uniqueid'])
                ->first();

            if(!empty($current_call)) {

                //RC: Miramos de marcar la ruta de entrada o de salida
                if($current_call->call_type_id == 1) {
                    $route_in = RouteIn::where('number', $current_call->to)
                        ->first();

                    if(!empty($route_in)) {
                        $current_call->route_in_id = $route_in->id;
                    }
                } else if($current_call->call_type_id == 2) {
                    $route_out = RouteOut::where('number', $current_call->from)
                        ->first();

                    if(!empty($route_out)) {
                        $current_call->route_out_id = $route_out->id;
                    }
                }

                //RC: Miramos si tenemos algun usuario activo en la llamada
                $old_current_call_user = CurrentCallUser::where('current_call_id', $current_call->id)
                    ->whereNull('duration')
                    ->first();

                if(!empty($old_current_call_user)) {
                    $old_current_call_user->duration = strtotime($this->payload['start']) - $old_current_call_user->start;
                    $old_current_call_user->save();
                    $current_call->call_status_id = 3;

                    if(!empty($old_current_call_user->user_id)) {
                        $old_user = User::findOrFail($old_current_call_user->user_id);
                        broadcast(new UpdateUserStatus($old_user));
                    }
                } else {
                    if($current_call->call_status_id != 5) {
                        $current_call->call_status_id = 4;
                    }
                }

                $current_call->duration = strtotime($this->payload['start']) - $current_call->start;
                $current_call->save();

                //RC: Generamos el registro del log
                $data_log['current_call_id'] = $current_call->id;
                $data_log['call_log_type_id'] = 10;
                $data_log['description'] = 'Finalizamos la llamada';
                $data_log['start'] = strtotime($this->payload['start']);
                CurrentCallLog::create($data_log);

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

                broadcast(new EventsCallHangup($call_stat, $current_call, $current_call->company_id));

            }
        }
    }
}
