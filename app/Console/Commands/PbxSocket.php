<?php

namespace App\Console\Commands;

use App\Jobs\PbxAgentConnect;
use App\Jobs\PbxAttendedTransfer;
use App\Jobs\PbxBridgeCreate;
use App\Jobs\PbxBridgeDestroy;
use App\Jobs\PbxBridgeEnter;
use App\Jobs\PbxBridgeLeave;
use App\Jobs\PbxDialBegin;
use App\Jobs\PbxDialEnd;
use App\Jobs\PbxExtensionStatus;
use App\Jobs\PbxHangup;
use App\Jobs\PbxHoldCall;
use App\Jobs\PbxNewcallerid;
use App\Jobs\PbxNewChannel;
use App\Jobs\PbxNewExten;
use App\Jobs\PbxQueueJoin;
use App\Jobs\PbxUnholdCall;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PbxSocket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pbxsocket';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Función para iniciar el socket';

    protected $sock = null;

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
        /*pcntl_signal(SIGINT,
            function ($signo) {
                echo "Finalizamos el socket";
                socket_close($this->sock);
                self::stop_socket_client();
                exit;
            }
        );

        pcntl_signal(SIGTERM, function ($signo) {
            echo "Finalizamos el socket";
            socket_close($this->sock);
            self::stop_socket_client();
            exit;
        });*/

        //RC: Permitir al script esperar para conexiones. 
        set_time_limit(0);

        //RC: Activar el volcado de salida implícito, así veremos lo que estamos obteniendo mientras llega.
        ob_implicit_flush();

        //RC: Finalizamos el socket del cliente
        self::stop_socket_client();

        //RC: Obtenemos la configuración
        $address = env('PBX_SOCKET_HOST', '');
        $port = env('PBX_SOCKET_PORT', '');

        if (empty($address) || empty($port)) {
            echo 'No tenemos configuración del socket';
            exit;
        }

        //RC: Generamos el socket
        if (($this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            echo "socket_create() falló: razón: " . socket_strerror(socket_last_error()) . "\n";
        }

        //RC: Enlazamos el socket a la dirección y el puerto indicados
        if (socket_bind($this->sock, $address, $port) === false) {
            echo "socket_bind() falló: razón: " . socket_strerror(socket_last_error($this->sock)) . "\n";
        }

        //RC: escuchamos para ver las conexiones
        if (socket_listen($this->sock, 5) === false) {
            echo "socket_listen() falló: razón: " . socket_strerror(socket_last_error($this->sock)) . "\n";
        }

        //RC: Lanzamos el socket de la centralita
        self::start_socket_client();

        while (true) {
            if (($msgsock = socket_accept($this->sock)) === false) {
                echo "socket_accept() falló: razón: " . socket_strerror(socket_last_error($this->sock)) . "\n";
                break;
            }

            while (true) {
                if (false === ($buf = socket_read($msgsock, 20480, PHP_NORMAL_READ))) {
                    echo "socket_read() falló: razón: " . socket_strerror(socket_last_error($msgsock)) . "\n";
                    break 2;
                }
                /*if (!$buf = trim($buf)) {
                    continue;
                }*/

                //RC: En este punto tenemos que procesar los distintos eventos
                $event = json_decode($buf, true);
                echo $buf . "\r\n";
                if (!empty($event['Event'])) {
                    switch ($event['Event']) {
                        case 'Newexten':
                            try {
                                PbxNewExten::dispatch($event);
                            } catch (Exception $e) {
                                $name = 'pbx_socket/errors_' . date('Ymd') . '.txt';
                                Storage::append($name, $e->getMessage());
                            }
                            break;
                        case 'NewCallerid':
                            try {
                                PbxNewcallerid::dispatch($event);
                            } catch (Exception $e) {
                                $name = 'pbx_socket/errors_' . date('Ymd') . '.txt';
                                Storage::append($name, $e->getMessage());
                            }
                            break;
                        case 'Newchannel':
                            try {
                                PbxNewChannel::dispatch($event);
                            } catch (Exception $e) {
                                $name = 'pbx_socket/errors_' . date('Ymd') . '.txt';
                                Storage::append($name, $e->getMessage());
                            }
                            break;
                        case 'QueueCallerJoin':
                            try {
                                PbxQueueJoin::dispatch($event);
                            } catch (Exception $e) {
                                $name = 'pbx_socket/errors_' . date('Ymd') . '.txt';
                                Storage::append($name, $e->getMessage());
                            }
                            break;
                        case 'AgentConnect':
                            try {
                                PbxAgentConnect::dispatch($event);
                            } catch (Exception $e) {
                                $name = 'pbx_socket/errors_' . date('Ymd') . '.txt';
                                Storage::append($name, $e->getMessage());
                            }
                            break;
                        case 'BridgeCreate':
                            try {
                                PbxBridgeCreate::dispatch($event);
                            } catch (Exception $e) {
                                $name = 'pbx_socket/errors_' . date('Ymd') . '.txt';
                                Storage::append($name, $e->getMessage());
                            }
                            break;
                        case 'BridgeEnter':
                            try {
                                PbxBridgeEnter::dispatch($event);
                            } catch (Exception $e) {
                                $name = 'pbx_socket/errors_' . date('Ymd') . '.txt';
                                Storage::append($name, $e->getMessage());
                            }
                            break;
                        case 'BridgeLeave':
                            try {
                                PbxBridgeLeave::dispatch($event);
                            } catch (Exception $e) {
                                $name = 'pbx_socket/errors_' . date('Ymd') . '.txt';
                                Storage::append($name, $e->getMessage());
                            }
                            break;
                        case 'BridgeDestroy':
                            try {
                                PbxBridgeDestroy::dispatch($event);
                            } catch (Exception $e) {
                                $name = 'pbx_socket/errors_' . date('Ymd') . '.txt';
                                Storage::append($name, $e->getMessage());
                            }
                            break;
                        case 'DialBegin':
                            try {
                                PbxDialBegin::dispatch($event);
                            } catch (Exception $e) {
                                $name = 'pbx_socket/errors_' . date('Ymd') . '.txt';
                                Storage::append($name, $e->getMessage());
                            }
                            break;
                        case 'DialEnd':
                            try {
                                PbxDialEnd::dispatch($event);
                            } catch (Exception $e) {
                                $name = 'pbx_socket/errors_' . date('Ymd') . '.txt';
                                Storage::append($name, $e->getMessage());
                            }
                            break;
                        case 'Hangup':
                            try {
                                PbxHangup::dispatch($event);
                            } catch (Exception $e) {
                                $name = 'pbx_socket/errors_' . date('Ymd') . '.txt';
                                Storage::append($name, $e->getMessage());
                            }
                            break;
                        case 'Hold':
                            try {
                                PbxHoldCall::dispatch($event);
                            } catch (Exception $e) {
                                $name = 'pbx_socket/errors_' . date('Ymd') . '.txt';
                                Storage::append($name, $e->getMessage());
                            }
                            break;
                        case 'Unhold':
                            try {
                                PbxUnholdCall::dispatch($event);
                            } catch (Exception $e) {
                                $name = 'pbx_socket/errors_' . date('Ymd') . '.txt';
                                Storage::append($name, $e->getMessage());
                            }
                            break;
                        case 'AttendedTransfer':
                            try {
                                PbxAttendedTransfer::dispatch($event);
                            } catch (Exception $e) {
                                $name = 'pbx_socket/errors_' . date('Ymd') . '.txt';
                                Storage::append($name, $e->getMessage());
                            }
                            break;
                        case 'ExtensionStatus':
                            try {
                                PbxExtensionStatus::dispatch($event);
                            } catch (Exception $e) {
                                $name = 'pbx_socket/errors_' . date('Ymd') . '.txt';
                                Storage::append($name, $e->getMessage());
                            }
                            break;
                    }
                }
            }
            socket_close($msgsock);
        }

        socket_close($this->sock);
    }

    public function shutdwown()
    {
        echo "Finalizamos el socket";
        socket_close($this->sock);
        self::stop_socket_client();
        exit;
    }

    private function start_socket_client()
    {
        $pbx_host = env('PBX_HOST', '');
        $pbx_port = env('PBX_PORT', '');

        if (!empty($pbx_host) && !empty($pbx_port)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $pbx_host . '/freePbxApi2/ami_server/start_socket_client.php');
            curl_setopt($ch, CURLOPT_PORT, $pbx_port);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            if (!$resp = curl_exec($ch)) {
                //RC: Si no tenemos respuesta marcamos la petición como error
                echo "error en la petición";
                curl_close($ch);
            } else {
                //RC: Si tenemos respuesta devolvemos el error
                curl_close($ch);
                echo $resp;
            }
        }
    }

    private function stop_socket_client()
    {
        $pbx_host = env('PBX_HOST', '');
        $pbx_port = env('PBX_PORT', '');

        if (!empty($pbx_host) && !empty($pbx_port)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $pbx_host . '/freePbxApi2/ami_server/stop_socket_client.php');
            curl_setopt($ch, CURLOPT_PORT, $pbx_port);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            if (!$resp = curl_exec($ch)) {
                //RC: Si no tenemos respuesta marcamos la petición como error
                echo "error en la petición";
                curl_close($ch);
            } else {
                //RC: Si tenemos respuesta devolvemos el error
                curl_close($ch);
                echo $resp;
            }
        }
    }
}
