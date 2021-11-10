<?php

namespace App\Console\Commands;

use App\Call;
use App\CallRecording;
use Illuminate\Console\Command;

class GetCallRecordingFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'getcallrecordingfile';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza el fichero de grabación de las llamadas';

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
        //RC: Solo queremos las llamadas de más de 5 mínutos
        $limit_time = strtotime('-5 minutes');

        echo "Límite de tiempo: ".$limit_time."\r\n";

        //RC: Obtenemos todas las llamadas
        $calls = Call::whereNull('recordingfile')
            ->where('start', '<', $limit_time)
            ->orderBy('id', 'asc')
            ->limit(50)
            ->get();

        //RC: obtenemos la configuración de la centralita
        $api_host = env('PBX_HOST', '');
        $api_port = env('PBX_PORT', '');

        if($api_host != '' && $api_port != '') {
            echo "Tenemos la configuración de la centralita\r\n";
            foreach($calls as $call) {
                echo "Buscamos la grabación de la llamada ".$call->id." (linkedid:".$call->linkedid.", uniqueid:".$call->uniqueid.")\r\n";
                $has_recordingfiles = false;
                
                //RC: Seteamos las variables para identificar la llamada
                $data['linkedid'] = $call->linkedid;
                $data['uniqueid'] = $call->uniqueid;
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_host.'/freePbxApi2/calls/sync_record.php');
                curl_setopt($ch, CURLOPT_PORT, $api_port);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

                if (!$resp = curl_exec($ch)) {
                    $response['error'] = true;
                    $response['resp'] = $resp;
                } else {
                    curl_close($ch);
                    $response = json_decode($resp);

                    if(!empty($response->recordingfiles)) {
                        $has_recordingfiles = true;
                        foreach($response->recordingfiles as $recordingfile) {
                            $call_record = new CallRecording();
                            $call_record->call_id = $call->id;
                            $call_record->recordingfile = $recordingfile;
                            $call_record->save();
                        }
                    }
                }

                //RC: Si tenemos grabación la guardamos, sino guardamos un carácter vacio
                if($has_recordingfiles) {
                    $call->recordingfile = 1;
                    $call->save();
                } else {
                    $call->recordingfile = 0;
                    $call->save();
                }
            }
        } else {
            echo "No tenemos configuración de la centralita\r\n";
        }
    }
}
