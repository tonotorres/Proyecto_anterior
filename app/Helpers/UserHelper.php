<?php

use App\BreakTimeUser;
use App\CurrentCallUser;
use App\Extension;
use App\UserSession;
use App\UserTemplateModule;
use Illuminate\Support\Facades\Auth;

if(!function_exists('get_user_module_securty')) {
   /**
    * @author Roger Corominas
    * Devuelve la configuración de seguridad del módulo indicado para el usuario logeado
    *
    * @param integer $module_id
    * @return void
    */
    function get_user_module_security(int $module_key) {
        $user = get_loged_user();
        $user_template_id = $user->user_template_id;
        return UserTemplateModule::generateQueryModuleByUserTempalateModuleKey($user_template_id, $module_key)
            ->first();
    }
}
if(!function_exists('get_loged_user')) {
    function get_loged_user() {
        return Auth::user();
    }
}

if(!function_exists('get_user_status')) {
    function get_user_status($user) {
        if (!empty($user) && !empty($user->id)) {
            $user_row['id'] = $user->id;
            $user_row['image'] = $user->image;
            $user_row['name'] = $user->name;
            $user_row['extension'] = $user->extension;
            $user_row['extension_status'] = 0;
            if (!empty($user->extension)) {
                $extension = Extension::where('number', $user->extension)
                    ->where('company_id', $user->company_id)
                    ->first();

                if (!empty($extension)) {
                    $user_row['extension_status'] = $extension->extension_status_id;
                }
            }
            
            $user_row['department_id'] = $user->department_id;

            $current_calls = CurrentCallUser::where('user_id', $user->id)
                ->whereNull('duration')
                ->count();

            if($current_calls > 0) {
                $user_row['status'] = 'Llamada en curso';
                $user_row['status_type'] = 'call';
            } else {
                $break_time = BreakTimeUser::where('user_id', $user->id)
                    ->whereNull('end')
                    ->first();

                if(!empty($break_time)) {
                    $user_row['status'] = $break_time->break_time->name;
                    $user_row['status_type'] = 'break_time';
                } else {
                    $user_session = UserSession::where('user_id', $user->id)
                        ->whereNull('end')
                        ->first();
                    
                    if($user_session) {
                        $user_row['status'] = 'Disponible';
                        $user_row['status_type'] = 'online';
                    } else {
                        $user_row['status'] = 'Desconectado';
                        $user_row['status_type'] = 'offline';
                    }
                }
                
            }
        } else {
            $user_row['status'] = '';
            $user_row['status_type'] = '';
        }

        return $user_row;
    }
}

if(!function_exists("start_user_session")) {
    /**
     * @author Roger Corominas
     * Inicia una sesión para el usuario indicado y finaliza la sesión que pueda tener abierta.
     *
     * @param  App\User $user usuario que se conecta
     * @param  string $latitude latitud desde donde se conecta
     * @param  string $longitude longitud desde donde se conecta
     * @return void
     */
    function start_user_session($user, string $latitude, string $longitude, string $extension = null)
    {
        //RC: Finalizamos la sesión anterior
        end_user_session($user->id, $latitude, $longitude);

        //RC: Generamos un nuevo registro
        $user_session = new UserSession();
        $user_session->user_id = $user->id;
        $user_session->company_id = $user->company_id;
        $user_session->extension = $extension;
        $user_session->start = date('Y-m-d H:i:s');
        $user_session->latitude_start = $latitude;
        $user_session->longitude_start = $longitude;
        if($_SERVER['REMOTE_ADDR']) {
            $user_session->ip_start = $_SERVER['REMOTE_ADDR'];
        }
        $user_session->save();
    }
}

if(!function_exists("end_user_session")) {
    /**
     * @author Roger Corominas
     * Finaliza la sesión de un usuario que está sin finalizar
     *
     * @param  int $user_id identificador del usuario
     * @param  string $latitude latitud desde donde se conecta
     * @param  string $longitude longitud desde donde se conecta
     * @return void
     */
    function end_user_session(int $user_id, string $latitude, string $longitude) {
        //RC: Obtenemos todas las sesiones abiertas
        $user_sessions = UserSession::where('user_id', $user_id)
            ->whereNull('end')
            ->get();

        foreach($user_sessions as $user_session) {
            //RC: Para cada sesión abierta cerramos y sumamos el tiempo.
            $user_session->end = date('Y-m-d H:i:s');
            $user_session->duration = strtotime($user_session->end) - strtotime($user_session->start);
            $user_session->latitude_end = $latitude;
            $user_session->longitude_end = $longitude;
            if (!empty($_SERVER) && !empty($_SERVER['REMOTE_ADDR'])) {
                $user_session->ip_end = $_SERVER['REMOTE_ADDR'];
            }
            
            //RC: Miramos si tenemos error con la IP del usaurio
            if($user_session->ip_start != $user_session->ip_end) {
                $user_session->ip_error = true;
            }

            //RC: Miramos si tenemos error con las coordenadas 
            if(!empty($user_session->latitude_start) && !empty($user_session->latitude_end) && !empty($user_session->longitude_start) && !empty($user_session->longitude_end)) {
                if(round($user_session->latitude_start, 2) != round($user_session->latitude_end, 2) || round($user_session->longitude_start, 2) != round($user_session->longitude_end, 2)) {
                    $user_session->coord_error = true;
                }
            }

            $user_session->save();
        }
    }
}

if(!function_exists("end_old_break_times")) {
    function end_old_break_times($user_id) {
        $break_time_users = BreakTimeUser::where('user_id', $user_id)
            ->whereNull('end')
            ->get();

        if(!empty($break_time_users)) {
            foreach ($break_time_users as $break_time_user) {
                end_break_time($break_time_user);
            }
        }
    }
}

if(!function_exists("end_break_time")) {
    function end_break_time($break_time_user) {
        $break_time_user->end = date('YmdHis');
        $start = strtotime(convert_int_to_time($break_time_user->start));
        $end = strtotime(convert_int_to_time($break_time_user->end));
        $break_time_user->duration = $end - $start;
        $break_time_user->save();

        return $break_time_user;
    }
}

if (!function_exists('getAdministrativeTimeFromUser')) {
    function getAdministrativeTimeFromUser($user)
    {
        if (!empty($user->department_id)) {
            $department = $user->department;

            if (!empty($department->administrative_time)) {
                $administrativeTime = $department->administrative_time;
            } else {
                $administrativeTime = 0;
            }
        } else {
            $administrativeTime = 0;
        }

        return $administrativeTime;
    }
}