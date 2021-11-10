<?php

namespace App\Jobs;

use App\Call;
use App\CurrentCall;
use App\Mail\SendReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class CallListReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $delimeter;
    private $company_id;
    private $start;
    private $end;
    private $department_id;
    private $user_id;
    private $page_limit = 1000;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($delimeter, $company_id, $start, $end, $department_id = null, $user_id = null)
    {
        $this->delimeter = $delimeter;
        $this->company_id = $company_id;
        $this->start = $start;
        $this->end = $end;
        $this->department_id = $department_id;
        $this->user_id = $user_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $start = strtotime($this->start);
        $end = strtotime($this->end);

        $callsQuery = Call::where('company_id', $this->company_id)
            ->where('calls.start', '>=', $start)
            ->where('calls.start', '<=', $end);

        if(!empty($this->department_id)) {
            $callsQuery->join('call_users', 'call_users.call_id', '=', 'calls.id')
                ->where('call_users.department_id', '=', $this->department_id)
                ->distinct()
                ->select('calls.*');
        }

        if(!empty($this->user_id)) {
            if(empty($this->department_id)) {
                $callsQuery->join('call_users', 'call_users.call_id', '=', 'calls.id')
                    ->where('call_users.user_id', '=', $this->user_id)
                    ->distinct()
                    ->select('calls.*');
            } else {
                $callsQuery->where('call_users.user_id', $this->user_id);
            }
        }

        $total_calls = $callsQuery->count();

        $total_pages = ceil($total_calls / $this->page_limit);


        $delimeter = $this->delimeter;
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
                ->limit(($i_page * $this->page_limit), $this->page_limit)
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

        Mail::to('rcorominas@netlusolucions.com')
            ->send(new SendReport(Storage::url($file_name)));
    }
}
