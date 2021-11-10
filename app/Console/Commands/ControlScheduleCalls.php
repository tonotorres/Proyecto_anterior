<?php

namespace App\Console\Commands;

use App\Events\NotifyScheduledCall;
use App\ScheduledCall;
use Illuminate\Console\Command;

class ControlScheduleCalls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'controlschedulecalls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Emite el evento para realizar la llamada';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //RC: Obtenemos todas las llamadas anteriores sin notificar
        $scheduled_calls = ScheduledCall::where('call_date', '<', date('Y-m-d H:i:s'))
            ->whereNull('notified_at')
            ->get();

        foreach ($scheduled_calls as $scheduled_call) {
            //RC: Marcamos como notificado
            $scheduled_call->notified_at = date('Y-m-d H:i:s');
            $scheduled_call->save();

            //RC: Emitimos el evento
            echo 'test 1';
            broadcast(new NotifyScheduledCall($scheduled_call));
            echo 'test 2';
        }
    }
}
