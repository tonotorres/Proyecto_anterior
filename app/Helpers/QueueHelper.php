<?php

use App\BreakTimeUser;
use App\CurrentCallUser;
use App\Extension;
use App\UserSession;
use App\UserTemplateModule;
use Illuminate\Support\Facades\Auth;

if (!function_exists('pause_all')) {
    function pause_all($user)
    {
        $api_host = env('PBX_HOST', '');
        $api_port = env('PBX_PORT', '');

        if ($user->extension) {
            $data['extension'] = $user->extension;

            if ($api_host != '' && $api_port != '') {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_host . '/freePbxApi2/queues/pause_all.php');
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
                }
            } else {
                $response['error'] = 'No tenemos centralita';
            }

            return response()->json($response, 200);
        }
    }
}

if (!function_exists('unpause_all')) {
    function unpause_all($user)
    {
        $api_host = env('PBX_HOST', '');
        $api_port = env('PBX_PORT', '');

        if ($user->extension) {
            $data['extension'] = $user->extension;

            if ($api_host != '' && $api_port != '') {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_host . '/freePbxApi2/queues/unpause_all.php');
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
                }
            } else {
                $response['error'] = 'No tenemos centralita';
            }

            return response()->json($response, 200);
        }
    }
}

if (!function_exists('pause_all_extension')) {

    /**
     * Funci??n para pausar de todas las colas de la centralita la extensi??n identificado por el n??mero $extension
     *
     * @param  String $extension
     * @return Object [error, resp]
     */
    function pause_all_extension($extension)
    {
        //RC: Obtenemos la configuraci??n de la centralita
        $api_host = env('PBX_HOST', '');
        $api_port = env('PBX_PORT', '');

        //RC: Validamos que la extensi??n no est?? vacia
        if (!empty($extension)) {
            $data['extension'] = $extension;

            if ($api_host != '' && $api_port != '') {
                //RC: Si tenemos la configuraci??n de una centralita realizamos la petici??n para pausar la extensi??n indicada
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_host . '/freePbxApi2/queues/pause_all.php');
                curl_setopt($ch, CURLOPT_PORT, $api_port);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

                if (!$resp = curl_exec($ch)) {
                    //RC: Si no tenemos respuesta marcamos la petici??n como error
                    $json_response['error'] = true;
                    $json_response['resp'] = $resp;
                } else {
                    //RC: Si tenemos respuesta devolvemos el error
                    curl_close($ch);
                    $json_response['error'] = false;
                    $json_response = json_decode($resp);
                }
            } else {
                //RC: Si no tenemos la configuraci??n de la centralita devolvemos error
                $json_response['error'] = true;
                $json_response['resp'] = 'No tenemos centralita';
            }
        } else {
            //RC: Si no tenemos extensi??n devolvemos el error
            $json_response['error'] = true;
            $json_response['resp'] = 'Pendiente indicar extensi??n';
        }

        return $json_response;
    }
}

if (!function_exists('unpause_all_extension')) {
    /**
     * Funci??n para quitar la pausa de las colas de la extensi??n indentificado con el n??mero $extensi??n a excepci??n de las colas pausadas de manera manual
     *
     * @param  String $extension
     * @return Object [error, resp]
     */
    function unpause_all_extension($extension)
    {
        //RC: Obtenemos la configuraci??n de la centralita
        $api_host = env('PBX_HOST', '');
        $api_port = env('PBX_PORT', '');

        //RC: Validamos que la extensi??n no est?? vacia
        if (!empty($extension)) {
            $data['extension'] = $extension;

            if ($api_host != '' && $api_port != '') {
                //RC: Si tenemos la configuraci??n de una centralita realizamos la petici??n para quitar la pausa de la extensi??n indicada
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_host . '/freePbxApi2/queues/unpause_all.php');
                curl_setopt($ch, CURLOPT_PORT, $api_port);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

                if (!$resp = curl_exec($ch)) {
                    //RC: Si no tenemos respuesta marcamos la petici??n como error
                    $json_response['error'] = true;
                    $json_response['resp'] = $resp;
                } else {
                    //RC: Si tenemos respuesta devolvemos el error
                    curl_close($ch);
                    $json_response['error'] = true;
                    $json_response = json_decode($resp);
                }
            } else {
                //RC: Si no tenemos la configuraci??n de la centralita devolvemos error
                $json_response['error'] = true;
                $json_response['error'] = 'No tenemos centralita';
            }
        } else {
            //RC: Si no tenemos extensi??n devolvemos el error
            $json_response['error'] = true;
            $json_response['resp'] = 'Pendiente indicar extensi??n';
        }

        return $json_response;
    }
}

if (!function_exists('get_user_queues')) {
    function get_user_queues($extension)
    {
        $data['extension'] = $extension;

        $api_host = env('PBX_HOST', '');
        $api_port = env('PBX_PORT', '');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_host . '/freePbxApi2/queues/get_extension_queues.php');
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
        }

        return $response->id;
    }
}

if (!function_exists('get_queue_users')) {
    function get_queue_users($queue)
    {
        $data['queue'] = $queue;

        $api_host = env('PBX_HOST', '');
        $api_port = env('PBX_PORT', '');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_host . '/freePbxApi2/queues/get_queue_extensions.php');
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
        }

        return $response->id;
    }
}

if (!function_exists('pause_queue')) {
    function pause_queue($queue, $extension)
    {
        $data['extension'] = $extension;
        $data['queue'] = $queue;

        $api_host = env('PBX_HOST', '');
        $api_port = env('PBX_PORT', '');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_host . '/freePbxApi2/queues/pause.php');
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
        }

        return $response;
    }
}

if (!function_exists('unpause_queue')) {
    function unpause_queue($queue, $extension)
    {
        $data['extension'] = $extension;
        $data['queue'] = $queue;

        $api_host = env('PBX_HOST', '');
        $api_port = env('PBX_PORT', '');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_host . '/freePbxApi2/queues/unpause.php');
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
        }

        return $response;
    }
}
