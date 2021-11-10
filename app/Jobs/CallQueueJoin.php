<?php

namespace App\Jobs;

use App\CurrentCall;
use App\CurrentCallLog;
use App\Events\CallUpdate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CallQueueJoin implements ShouldQueue
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
        if(!empty($this->payload['company_id']) && !empty($this->payload['linkedid']) && !empty($this->payload['queue']) && !empty($this->payload['start'])) {
            //RC: Miramos si tenemos la llamada
            $current_call = CurrentCall::where('linkedid', $this->payload['linkedid'])
                ->where('company_id', $this->payload['company_id'])
                ->first();

            if(!empty($current_call)) {
                $current_call->queue = $this->payload['queue'];
                $current_call->save();

                //RC: Guardamos un registro en el log
                $data_log['current_call_id'] = $current_call->id;
                $data_log['call_log_type_id'] = 2;
                $data_log['description'] = 'Entramos en la cola '.$this->payload['queue'];
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
                $call_stat['queue'] = $current_call->queue;
                $call_stat['call_type_id'] = $current_call->call_type_id;
                $call_stat['call_status_id'] = $current_call->call_status_id;

                broadcast(new CallUpdate($call_stat, $current_call, $current_call->company_id));
            }
        }
    }
}
