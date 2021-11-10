<?php

namespace App\Console\Commands;

use App\CurrentCall;
use App\CurrentCallLog;
use App\CurrentCallUser;
use App\Events\CallHangup;
use App\Events\UpdateUserStatus;
use App\Jobs\CurrentCallToCall;
use App\RouteIn;
use App\RouteOut;
use App\User;
use Illuminate\Console\Command;

class CloseCalls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'closecalls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cerramos las llamadas que quedan abiertas por problemas con los eventos';

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

        //Eliminamos todas las llamadas internas que sean códigos
        CurrentCall::where('to', 'like', '*%')->delete();

        $current_calls = CurrentCall::whereNull('duration')
            ->get();

        $api_host = env('PBX_HOST', '');
        $api_port = env('PBX_PORT', '');

        foreach ($current_calls as $current_call) {
            echo $current_call->id . "\r\n";
            $gap = strtotime('now') - $current_call->start;

            if ($gap >= 300) {
                echo $current_call->id . " > superior a 300 segundos\r\n";
                if ($current_call->to == 's') {
                    echo $current_call->id . " > destino es s\r\n";
                    //RC: Emitimos el evento de update call
                    $call_stat['id'] = $current_call->id;
                    $call_stat['from'] = $current_call->from;
                    $call_stat['to'] = $current_call->to;
                    $call_stat['start'] = $current_call->start;
                    $call_stat['duration'] = 0;
                    $call_stat['user_id'] = null;
                    $call_stat['user_name'] = null;
                    $call_stat['department_id'] = $current_call->department_id;;
                    $call_stat['extension'] = null;
                    $call_stat['queue'] = null;
                    $call_stat['call_type_id'] = $current_call->call_type_id;
                    $call_stat['call_status_id'] = $current_call->call_status_id;

                    broadcast(new CallHangup($call_stat, $current_call, $current_call->company_id));
                    $current_call->delete();
                } else if ($api_host != '' && $api_port != '') {
                    echo $current_call->id . " > tenemos configuración de la centralita\r\n";
                    $data['linkedid'] = $current_call->linkedid;
                    $data['uniqueid'] = $current_call->uniqueid;

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $api_host . '/freePbxApi2/calls/get_call_info.php');
                    curl_setopt($ch, CURLOPT_PORT, $api_port);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

                    if (!$resp = curl_exec($ch)) {
                    } else {
                        curl_close($ch);
                        $response = json_decode($resp, true);
                        if (!empty($response['cdr'])) {
                            //RC: Miramos de marcar la ruta de entrada o de salida
                            if ($current_call->call_type_id == 1) {
                                $route_in = RouteIn::where('number', $current_call->to)
                                    ->first();

                                if (!empty($route_in)) {
                                    $current_call->route_in_id = $route_in->id;
                                }
                            } else if ($current_call->call_type_id == 2) {
                                $route_out = RouteOut::where('number', $current_call->from)
                                    ->first();

                                if (!empty($route_out)) {
                                    $current_call->route_out_id = $route_out->id;
                                }
                            }

                            //RC: Miramos si tenemos algun usuario activo en la llamada
                            $old_current_call_user = CurrentCallUser::where('current_call_id', $current_call->id)
                                ->whereNull('duration')
                                ->first();

                            if (!empty($old_current_call_user)) {
                                $old_current_call_user->duration = $current_call->start + $response['cdr']['duration'] - $old_current_call_user->start;
                                $old_current_call_user->save();
                                $current_call->call_status_id = 3;

                                if (!empty($old_current_call_user->user_id)) {
                                    $old_user = User::findOrFail($old_current_call_user->user_id);
                                    broadcast(new UpdateUserStatus($old_user));
                                }
                            } else {
                                if ($current_call->call_status_id != 5) {
                                    $current_call->call_status_id = 4;
                                }
                            }

                            $current_call->duration = $current_call->start + $response['cdr']['duration'] - $current_call->start;
                            $current_call->save();

                            //RC: Generamos el registro del log
                            $data_log['current_call_id'] = $current_call->id;
                            $data_log['call_log_type_id'] = 10;
                            $data_log['description'] = 'Finalizamos la llamada';
                            $data_log['start'] = $current_call->start + $response['cdr']['duration'];
                            // CurrentCallLog::create($data_log);

                            //RC: Emitimos el evento de update call
                            $call_stat['id'] = $current_call->id;
                            $call_stat['from'] = $current_call->from;
                            $call_stat['to'] = $current_call->to;
                            $call_stat['start'] = $current_call->start;
                            $call_stat['duration'] = $response['cdr']['duration'];
                            $call_stat['user_id'] = null;
                            $call_stat['user_name'] = null;
                            $call_stat['department_id'] = $current_call->department_id;;
                            $call_stat['extension'] = null;
                            $call_stat['queue'] = null;
                            $call_stat['call_type_id'] = $current_call->call_type_id;
                            $call_stat['call_status_id'] = $current_call->call_status_id;

                            broadcast(new CallHangup($call_stat, $current_call, $current_call->company_id));
                        }
                    }
                }
            }
        }

        $current_calls = CurrentCall::whereNotNull('duration')
        ->get();

        foreach ($current_calls as $current_call) {
            echo $current_call->id . " > la llamada está finalizada\r\n";

            //RC: Emitimos el evento de update call
            $call_stat['id'] = $current_call->id;
            $call_stat['from'] = $current_call->from;
            $call_stat['to'] = $current_call->to;
            $call_stat['start'] = $current_call->start;
            $call_stat['duration'] = $current_call->duration;
            $call_stat['user_id'] = null;
            $call_stat['user_name'] = null;
            $call_stat['department_id'] = $current_call->department_id;;
            $call_stat['extension'] = null;
            $call_stat['queue'] = null;
            $call_stat['call_type_id'] = $current_call->call_type_id;
            $call_stat['call_status_id'] = $current_call->call_status_id;

            broadcast(new CallHangup($call_stat, $current_call, $current_call->company_id));

            CurrentCallToCall::dispatch($current_call->id);
        }
    }
}
