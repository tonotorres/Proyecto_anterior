<?php

namespace App\Jobs;

use App\Call;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MsDynamicsSyncCall implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $call_id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($call_id)
    {
        $this->call_id = $call_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //RC: Obtenemos la llamada
        $call = Call::findOrFail($this->call_id);

        //RC: Generamos la cabecera
        $header_call['id'] = $call->id;
        $header_call['call_type_id'] = $call->call_type_id;
        $header_call['call_status_id'] = $call->call_status_id;
        $header_call['call_end_id'] = $call->call_end_id;
        $header_call['department_id'] = $call->department_id;

        //RC: en la cabecera de la llamada indicamos el último usuario que habló
        if (!empty($call->call_users)) {
            $header_call['user_id'] = $call->call_users()->orderBy('id', 'desc')->first()->user_id;
        } else {
            $header_call['user_id'] = null;
        }

        $header_call['account_id'] = $call->account_id;
        $header_call['from'] = $call->from;
        $header_call['to'] = $call->to;
        $header_call['start'] = $call->start;
        $header_call['duration'] = $call->duration;
        $header_call['duration_wait'] = $call->duration_wait;
        $header_call['recordingfile'] = $call->recordingfile;

        //RC: Mandamos la cabecera al API

        //RC: Para cada usuario generamos la estructura
        foreach ($call->call_users as $call_user) {
            unset($line_call);
            $line_call = [];

            $line_call['call_id'] = $call_user->call_id;
            $line_call['user_id'] = $call_user->user->code;
            $line_call['extension'] = $call_user->extension;
            $line_call['start'] = $call_user->start;
            $line_call['duration'] = $call_user->duration;

            //RC: Mandamos la línea al API
        }
    }
}
