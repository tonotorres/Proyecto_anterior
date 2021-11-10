<?php

namespace App\Console\Commands;

use App\CurrentCall;
use App\Jobs\CurrentCallToCall as JobCurrentCallToCall;
use Illuminate\Console\Command;

class CurrentCallsToCall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currentcallstocalls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Selecciona todas las llamadas actuales finalizadas y las copia a la tabla principal';

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
        $current_calls = CurrentCall::whereNotNull('duration')
            ->get();

        foreach($current_calls as $current_call) {
            JobCurrentCallToCall::dispatch($current_call->id);
            sleep(1);
        }
    }
}
