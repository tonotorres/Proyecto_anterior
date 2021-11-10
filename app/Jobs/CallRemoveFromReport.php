<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CallRemoveFromReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $call;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($call)
    {
        $this->call = $call;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        remove_report_call($this->call);
        remove_report_duration_call($this->call);
        if($this->call_type_id == 1) {
            remove_report_wait_call($this->call);
        }
    }
}
