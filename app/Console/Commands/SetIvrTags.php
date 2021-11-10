<?php

namespace App\Console\Commands;

use App\Ivr;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetIvrTags extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setivrtags';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marca las etiquetas de los ivrs en las llamadas ya procesadas';

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
        $ivrs = Ivr::all();

        foreach($ivrs as $ivr) {
            if(!empty($ivr->pbx_id) && !empty($ivr->tag_id)) {
                DB::update("UPDATE call_ivrs INNER JOIN calls ON call_ivrs.call_id = calls.id SET call_ivrs.ivr_tag_id = ".$ivr->tag_id." WHERE calls.company_id = ".$ivr->company_id." AND call_ivrs.pbx_ivr = ".$ivr->pbx_id. " AND call_ivrs.ivr_tag_id IS NULL");
            }

            foreach($ivr->ivr_options as $ivr_option) {
                if(!empty($ivr_option->ivr->pbx_id) && !empty($ivr_option->tag_id)) {
                    DB::update("UPDATE call_ivrs INNER JOIN calls ON call_ivrs.call_id = calls.id SET call_ivrs.ivr_option_tag_id = ".$ivr_option->tag_id." WHERE calls.company_id = ".$ivr_option->ivr->company_id." AND call_ivrs.pbx_ivr = ".$ivr_option->ivr->pbx_id. " AND call_ivrs.option = '".$ivr_option->option."' AND call_ivrs.ivr_option_tag_id IS NULL");
                }
            }
        }
    }
}
