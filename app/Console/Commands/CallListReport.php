<?php

namespace App\Console\Commands;

use App\Call;
use App\DelayedReport;
use App\Mail\SendReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class CallListReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calllistreport';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera la exportación del listado de llamadas';

    private $page_limit = 100;

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

        $delayed_reports = DelayedReport::where('finished', 0)
            ->where('name', 'CallListReport')
            ->get();

        foreach($delayed_reports as $delayed_report) {
            $data = json_decode($delayed_report->data);

            $start = strtotime($data->start . ' 00:00:00');
            $end = strtotime($data->end . ' 23:59:59');

            $callsQuery = Call::where('company_id', $data->company_id)
                ->where('calls.start', '>=', $start)
                ->where('calls.start', '<=', $end);

            if(!empty($data->department_id)) {
                $callsQuery->join('call_users', 'call_users.call_id', '=', 'calls.id')
                    ->where('call_users.department_id', '=', $data->department_id)
                    ->distinct()
                    ->select('calls.*');
            }

            if(!empty($data->user_id)) {
                if(empty($data->department_id)) {
                    $callsQuery->join('call_users', 'call_users.call_id', '=', 'calls.id')
                        ->where('call_users.user_id', '=', $data->user_id)
                        ->distinct()
                        ->select('calls.*');
                } else {
                    $callsQuery->where('call_users.user_id', $data->user_id);
                }
            }

            $total_calls = $callsQuery->count();

            $total_pages = ceil($total_calls / $this->page_limit);


            $delimeter = $data->delimeter;
            if($delimeter == ',') {
                $user_delimeter = ';';
            } else {
                $user_delimeter = ',';
            }

            $file_name = 'temp/'.date('Y').'/'.date('m').'/report_'.date('YmdHis').'.csv';

            $str_csv = "Fecha;Tipo;Estado;Departamento;De;Para;Cuenta;Duración de espera;Duración;Usuarios;Final";
            Storage::disk('public')->append($file_name, utf8_decode($str_csv));

            for($i_page = 0; $i_page < $total_pages; $i_page++ ) {
                $calls = $callsQuery->orderBy('calls.start', 'asc')
		            ->offset($i_page * $this->page_limit)
                    ->limit($this->page_limit)
                    ->get();

                $str_csv = '';

                foreach($calls as $call) {
                    $str_csv .= date('d/m/Y H:i:s', $call->start).$delimeter;
                    $str_csv .= $call->call_type->name.$delimeter;
                    $str_csv .= $call->call_status->name.$delimeter;
                    if($call->department_id) {
                        $str_csv .= $call->department->name.$delimeter;
                    } else {
                        $str_csv .= $delimeter;
                    }
                    $str_csv .= $call->from.$delimeter;
                    $str_csv .= $call->to.$delimeter;
                    if($call->account_id && !empty($call->account)) {
                        $str_csv .= $call->account->name.$delimeter;
                    } else {
                        $str_csv .= $delimeter;
                    }
                    $str_csv .= $call->duration_wait.$delimeter;
                    $str_csv .= $call->duration.$delimeter;

                    $i = 0;
                    foreach($call->call_users as $call_user) {
                        if($i > 0) {
                            $str_csv .= $user_delimeter.' ';
                        }
                        if(!empty($call_user->user_id) && !empty($call_user->user)) {
                            $str_csv .= $call_user->user->name;
                        } else {
                            $str_csv .= $call_user->extension;
                        }

                        $i++;
                    }
                    $str_csv .= $delimeter;

                    if(!empty($call->call_end_id)) {
                        $str_csv .= $call->call_end->name.$delimeter;
                    } else {
                        $str_csv .= $delimeter;
                    }

                    $str_csv .= "\r\n";

                }

                Storage::disk('public')->append($file_name, utf8_decode($str_csv));
            }

            Mail::to($data->email)
                ->send(new SendReport(Storage::url($file_name)));

            $delayed_report->finished = 1;
            $delayed_report->save();
        }
    }
}
