<?php

namespace App\Console\Commands;

use App\Call;
use App\CallUser;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportOldCalls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'importoldcalls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importamos las llamadas de la versión anterior';

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
        $total_old_calls = DB::select("SELECT count(*) as total FROM old_calls");

        $page = 1000;
        $total = $total_old_calls[0]->total;
        $total_pages = ceil($total / $page);

        for($i = 0; $i < $total_pages; $i++) {
            $start = $i * $page;
            $limit = $start.', '.$page;
            $old_calls = DB::select("SELECT * FROM old_calls ORDER BY id asc LIMIT $limit");

            foreach($old_calls as $old_call) {
                if(Call::where('linkedid', $old_call->linkedid)->count() == 0) {
                    echo $old_call->linkedid."\r\n";
                    $old_users = DB::select("SELECT * FROM old_call_users WHERE call_id = ".$old_call->id." ORDER BY id ASC");

                    $call = new Call();
                    $call->company_id = 1;
                    $call->call_type_id = $old_call->call_type_id;
                    $call->call_status_id = $old_call->call_status_id;
                    $call->call_end_id = $old_call->call_end_id;
                    if($call->call_type_id == 1) {
                        $call->from = $old_call->callerid;
                        $call->to = $old_call->ddi;
                    } else {
                        $call->from = $old_call->ddi;
                        $call->to = $old_call->callerid;
                    }
                    $call->queue = $old_call->queue;
                    if(!empty($old_call->uniqueid)) {
                        $call->uniqueid = $old_call->uniqueid;
                    } else {
                        $call->uniqueid = '';
                    }
                    $call->linkedid = $old_call->linkedid;
                    $call->start = strtotime($old_call->start);
                    $call->duration_wait = $old_call->duration_wait;
                    $call->duration = $old_call->duration;
                    $call->save();

                    foreach($old_users as $old_user) {
                        if(!empty($old_user->user_id)) {
                            $user = User::where('id', $old_user->user_id)->first();
                            if(!empty($user)) {
                                $user_id = $user->id;
                            } else {
                                $user_id = null;
                            }

                        }
                        $call_user = new CallUser();
                        $call_user->call_id = $call->id;
                        $call_user->user_id = $user_id;
                        $call_user->department_id = $old_user->department_id;
                        if(!empty($call_user->extension = $old_user->extension)) {
                            $call_user->extension = $old_user->extension;
                        } else {
                            $call_user->extension = '';
                        }
                        $call_user->start = strtotime($old_user->start);
                        $call_user->duration = $old_user->duration;
                        $call_user->save();

                        if(!empty($call_user->department_id)) {
                            $call->department_id = $call_user->department_id;
                            $call->save();
                        }
                    }
                } else {
                    echo $old_call->linkedid." -> REPETIDA!!!! \r\n";
                }
            }
        }
    }
}
