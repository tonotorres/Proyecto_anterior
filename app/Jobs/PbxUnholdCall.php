<?php

namespace App\Jobs;

use App\CurrentCall;
use App\Events\CallUpdate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PbxUnholdCall implements ShouldQueue
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
        //RC: Obtenemos la llamada
        if ($this->data['company_id'] == -1) {
            $current_call = CurrentCall::where('linkedid', $this->data['Linkedid'])
                ->first();

            if (!empty($current_call)) {
                $this->data['company_id'] = $current_call->company_id;
            }
        } else {
            $current_call = CurrentCall::where('linkedid', $this->data['Linkedid'])
                ->where('company_id', $this->data['company_id'])
                ->first();
        }

        //RC: Actualizamos el estado
        if (!empty($current_call)) {
            $current_call->call_status_id = 2;
            $current_call->save();

            //RC: Obtenemos el usuario activo
            $user = $current_call->call_users()->where('duration', 'null')->orderBy('id', 'DESC')->first();

            //RC: Obtenemos el número de la extensión
            $number = get_channel_name($this->data['Channel']);

            //RC: Emitimos el evento de update call
            $call_stat['id'] = $current_call->id;
            $call_stat['from'] = $current_call->from;
            $call_stat['to'] = $current_call->to;
            $call_stat['start'] = $current_call->start;
            $call_stat['duration'] = strtotime('now') - $current_call->start;
            if (!empty($user)) {
                $call_stat['user_id'] = $user->id;
                $call_stat['user_name'] = $user->name;
                $call_stat['extension'] = $number;
                if (!empty($user->department_id)) {
                    $call_stat['department_id'] = $user->department_id;
                    $current_call->department_id = $user->department_id;
                    $current_call->save();
                } else {
                    $call_stat['department_id'] = null;
                }
            } else {
                $call_stat['user_id'] = null;
                $call_stat['department_id'] = $current_call->department_id;;
                $call_stat['user_name'] = $number;
                $call_stat['extension'] = $number;
            }
            $call_stat['queue'] = $current_call->queue;
            $call_stat['call_type_id'] = $current_call->call_type_id;
            $call_stat['call_status_id'] = $current_call->call_status_id;
            $call_stat['event'] = 'agent_connect';

            broadcast(new CallUpdate($call_stat, $current_call, $current_call->company_id));
        }
    }
}
