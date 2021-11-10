<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteIvrDuplicates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deleteivrduplicates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina los duplicados de los IVRs de la nueva versión de freepbx';

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
        DB::update("UPDATE call_ivrs ci1 INNER JOIN call_ivrs ci2 ON ci1.call_id = ci2.call_id AND ci1.pbx_ivr = ci2.pbx_ivr AND ci1.id <> ci2.id SET ci1.`option` = 'ss' WHERE ci1.`option` = 's'");
        DB::delete("DELETE FROM call_ivrs WHERE call_ivrs.`option` = 'ss'");
    }
}
