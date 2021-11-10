<?php

use App\AccountContactType;
use App\CallUserAdministrativeTime;
use App\CurrentCall;
use App\CurrentCallUser;
use App\CurrentCallUserCalled;
use App\Events\CallStart;
use App\Events\CallUpdate;
use App\Events\UpdateUserStatus;
use App\Extension;
use App\Jobs\FinishUserAdministrativeTime;
use App\PbxChannel;
use App\PbxChannelState;
use App\PhoneNumber;
use App\RouteIn;
use App\Trunk;
use App\User;

if(!function_exists('get_country_code')) {
    function get_country_code($phone) {
        if(substr($phone, 0, 2) == '00') {
            $country_code = '';
            $end = false;
            $length = 6;
            while(!$end) {
                $cc = substr($phone, 2, $length);
                $cc_reg = \App\CountryCode::where('code', $cc)->first();
                if(!empty($cc_reg)) {
                    $country_code = $cc;
                    $end = true;
                } else {
                    $length--;
                    if ($length < 2) {
                        $end = true;
                    }
                }
            }
        } else if(substr($phone, 0, 1) == '+') {
            $country_code = '';
            $end = false;
            $length = 6;
            while(!$end) {
                $cc = substr($phone, 1, $length);
                $cc_reg = \App\CountryCode::where('code', $cc)->first();
                if(!empty($cc_reg)) {
                    $country_code = $cc;
                    $end = true;
                } else {
                    $length--;
                    if ($length < 2) {
                        $end = true;
                    }
                }
            }
        } else {
            $country_code = '';
        }

        return $country_code;
    }
}

if(!function_exists('clean_country_code')) {
    function clean_country_code($phone) {
        /*$country_code = get_country_code($phone);

        if(!empty($country_code)) {
            if(substr($phone, 0, 2) == '00') {
                $clean_phone = substr($phone, 2 + strlen($country_code));
            } else if(substr($phone, 0, 1) == '+') {
                $clean_phone = substr($phone, 1 + strlen($country_code));
            } else {
                $clean_phone = $phone;
            }
        } else {
            $clean_phone = $phone;
        }*/

        $clean_phone = $phone;

        return $clean_phone;
    }
}

if(!function_exists('plus_country_code')) {
    function plus_country_code($phone) {
        if(substr($phone, 0, 2) == '00') {
            $clean_phone = '+'.substr($phone, 2);
        } else {
            $clean_phone = $phone;
        }

        return $clean_phone;
    }
}

if (!function_exists('create_call_from_ami_event')) {
    /**
     * @author Roger Corominas
     * Función que genera una llamada con la información del evento del AMI (personalizada con la hora del registro y la compañía)
     * @param Array data Array con la información del evento + start y company_id
     */
    function create_call_from_ami_event($data)
    {
        //RC: Obtenemos los valores de quien llama y donde llama (pueden ser desconocidos)
        $from = $data['CallerIDNum'];
        $to = $data['Exten'];

        $trunk_name = get_channel_name($data['Channel']);

        //RC: Miramos si tenemos algun troncal con este nombre
        $trunk = Trunk::whereRaw("LOWER(trunks.name) like '" . strtolower($trunk_name) . "'")
            ->first();

        if (!empty($trunk)) {
            //RC: tenemos una llamada entrante
            $call_type_id = 1;
            $call_status_id = 1;

            //RC: Si no tenemos compañía la tenemos que buscar por el número de destino
            if ($data['company_id'] == -1) {
                $phone_number = PhoneNumber::where('number', $to)
                    ->first();
                if (!empty($phone_number)) {
                    $data['company_id'] = $phone_number->company_id;
                } else {
                    //RC: Si no tenemos compañía no podemos continuar
                    return null;
                }
            }

            //RC: Miramos de que ruta de entrada viene
            $route_in = RouteIn::where('number', $to)->first();
            if (!empty($route_in)) {
                $data_save['route_in_id'] = $route_in->id;
            }
        } else {
            //RC: Tenemos una llamada interna
            $call_type_id = 3;
            $call_status_id = 2;

            //RC: Si no tenemos compañía asignada la tenemos que buscar por la extensión
            if ($data['company_id'] == -1) {
                $extension = Extension::where('number', $from)
                    ->first();
                if (!empty($extension)) {
                    $data['company_id'] = $extension->company_id;
                } else {
                    //RC: Si no tenemos compañía no podemos continuar
                    return null;
                }
            }
        }

        //RC: Miramos si es de un cotnacto o de una cuenta
        if (
            $call_type_id == 1
        ) {
            $contact_phone = $from;
        } else if ($call_type_id >= 2) {
            $contact_phone = $to;
        }

        if (!empty($data['company_id']) && $data['company_id'] > 0) {
            $account_contact_type = get_account_type_by_number($data['company_id'], $contact_phone);
        }

        if (!empty($account_contact_type)) {
            $account_id = $account_contact_type->account_id;
        } else {
            $account_id = null;
        }

        //RC: Generamos la llamada
        $data_save['company_id'] = $data['company_id'];
        $data_save['call_type_id'] = $call_type_id;
        $data_save['call_status_id'] = $call_status_id;
        $data_save['account_id'] = $account_id;
        $data_save['uniqueid'] = $data['Uniqueid'];
        $data_save['linkedid'] = $data['Linkedid'];
        $data_save['from'] = $from;
        $data_save['to'] = $to;
        $data_save['start'] = $data['start'];
        $current_call = CurrentCall::create($data_save);

        if ($call_type_id == 3) {
            //RC: Si tenemos una llamada interna podemos guardar el registro de la extensión de origen
            $extension = $current_call->from;

            startCurrentCallUser($current_call, $extension, $current_call->start);            
        }

        return $current_call;
    }
}

if (!function_exists('get_extension_interface')) {
    function get_extension_interface($interface)
    {
        //RC: Obtenemos el nombre del troncal
        $start = strpos($interface, '/') + 1;
        $length = strpos($interface, '@') - $start;
        return substr($interface, $start, $length);
    }
}

if (!function_exists('get_channel_name')) {
    function get_channel_name($channel_name)
    {
        //RC: Obtenemos el nombre del troncal
        $start = strpos($channel_name, '/') + 1;
        $length = strpos($channel_name, '-') - $start;
        return substr($channel_name, $start, $length);
    }
}

if (!function_exists('get_channel_prefix')) {
    function get_channel_prefix($channel_name)
    {
        //RC: Obtenemos el nombre del troncal
        $start = 0;
        $length = strpos($channel_name, '/');
        return substr($channel_name, $start, $length);
    }
}

if (!function_exists('get_extension_user')) {
    function get_extension_user($company_id, $extension)
    {
        return User::join('user_sessions', 'user_sessions.user_id', '=', 'users.id')
            ->where('user_sessions.extension', $extension)
            ->where('users.company_id', $company_id)
            ->orderBy('user_sessions.id', 'DESC')
            ->select('users.*')
            ->first();
    }
}

if (!function_exists('creat_pbx_channel_from_ami_event')) {
    function creat_pbx_channel_from_ami_event($data, $current_call_id)
    {
        $pbx_channel_state = PbxChannelState::where('key', $data['ChannelState'])->first();

        if (empty($pbx_channel_state)) {
            $pbx_channel_state = new PbxChannelState();
            $pbx_channel_state->key = $data['ChannelState'];
            $pbx_channel_state->name = $data['ChannelStateDesc'];
            $pbx_channel_state->save();
        }

        //RC: Miramos si ya tenemos el canal generado por otro evento
        $pbx_channel = PbxChannel::where('name', $data['Channel'])->first();

        if (empty($pbx_channel)) {
            $pbx_channel = new PbxChannel();
        }

        $pbx_channel->name = $data['Channel'];
        $pbx_channel->pbx_channel_state_id = $pbx_channel_state->id;
        $pbx_channel->current_call_id = $current_call_id;
        $pbx_channel->callerid = $data['CallerIDNum'];
        $pbx_channel->linkedid = $data['Linkedid'];
        $pbx_channel->uniqueid = $data['Uniqueid'];
        $pbx_channel->save();

        return $pbx_channel;
    }
}

if (!function_exists('get_company_id_by_number')) {
    function get_company_id_by_number($number)
    {
        $phone_number = PhoneNumber::where('number', $number)
            ->first();

        if (!empty($phone_number)) {
            return $phone_number->company_id;
        } else {
            //RC: Si no tenemos número, miramos si tenemos una extensión
            $extension = Extension::where('number', $number)
                ->first();

            if (!empty($extension)) {
                return $extension->company_id;
            } else {
                return -1;
            }
        }
    }
}

if (!function_exists('generate_event_call_start')) {
    function generate_event_call_start($current_call, $event_type, $call_stat_event = '')
    {
        //RC: Emitimos el evento de nueva llamada
        $call_stat['id'] = $current_call->id;
        $call_stat['from'] = $current_call->from;
        $call_stat['to'] = $current_call->to;
        $call_stat['start'] = $current_call->start;
        $call_stat['duration'] = strtotime('now') - $current_call->start;
        $call_stat['queue'] = null;
        $call_stat['call_type_id'] = $current_call->call_type_id;
        $call_stat['call_status_id'] = $current_call->call_status_id;
        $call_stat['event'] = $call_stat_event;

        $current_call_user = $current_call->call_users()->whereNull('duration')->orderBy('start', 'desc')->first();

        //RC: miramos si tenemos un usuario activo
        if (!empty($current_call_user)) {
            if (!empty($current_call_user->user_id)) {
                $call_stat['user_id'] = $current_call_user->user_id;
                $call_stat['user_name'] = $current_call_user->user->name;
                $call_stat['department_id'] = $current_call_user->user->department_id;
            } else {
                $call_stat['user_id'] = null;
                $call_stat['user_name'] = null;
                $call_stat['department_id'] = null;
            }
            $call_stat['extension'] = $current_call_user->extension;
        } else {
            $call_stat['user_id'] = null;
            $call_stat['user_name'] = null;
            $call_stat['department_id'] = null;
            $call_stat['extension'] = null;
        }

        if ($event_type == 'EventsCallStart') {
            broadcast(new CallStart($call_stat, $current_call, $current_call->company_id));
        } else {
            broadcast(new CallUpdate($call_stat, $current_call, $current_call->company_id));
        }
    }
}

if (!function_exists('get_account_type_by_number')) {
    function get_account_type_by_number($company_id, $number)
    {
        return AccountContactType::join('accounts', 'accounts.id', '=', 'account_contact_types.account_id')
            ->where('accounts.company_id', $company_id)
            ->where(function ($query) use ($number) {
                $query->orWhere('account_contact_types.value', $number)
                    ->orWhere('account_contact_types.value', str_replace('+', '00', $number))
                    ->orWhere('account_contact_types.value', substr($number, 1))
                    ->orWhere('account_contact_types.value', '+' . $number)
                    ->orWhere('account_contact_types.value', '+34' . $number)
                    ->orWhere('account_contact_types.value', '+34' . substr($number, 1))
                    ->orWhereRaw('REPLACE(account_contact_types.value, "+", "00") = "' . substr($number, 1) . '"')
                    ->orWhereRaw('REPLACE(account_contact_types.value, "+", "00") = "' . $number . '"');
            })
            ->select('account_contact_types.*')
            ->first();
    }
}

if (!function_exists('getCurrentCallByLinkedid')) {
    /**
     * getCurrentCallByLinkedid devuelve la llamada de identificada por linkedid y en caso de tener un idendificador de organización de esa organización.
     * @author Roger Corominas
     *
     * @param  String $linkedid identificador de la llamada
     * @param  int $company_id identificador de la organización o -1 si es compartido
     * @return App\CurrentCall
     */
    function getCurrentCallByLinkedid(String $linkedid, int $company_id)
    {
        if ($company_id == -1) {
            $currentCall = CurrentCall::where('linkedid', $linkedid)
                ->first();
        } else {
            $currentCall = CurrentCall::where('linkedid', $linkedid)
                ->where('company_id', $company_id)
                ->first();
        }

        return $currentCall;
    }
}

if (!function_exists('finishCurrentCallUserByExtension')) {
    /**
     * finshCurrentCallUserByExtension finaliza el tramo de la llamada asignado a una extensión
     * @author Roger Corominas
     *
     * @param  App\CurrentCall $currentCall
     * @param  String $extension
     * @param  Integer $dateStart
     * @return App\CurrentCallUser
     */
    function finishCurrentCallUserByExtension($currentCall, $extension, $dateStart)
    {
        //RC: obtenemos el usuario activo de la llamada
        $currentCallUser = $currentCall->call_users()
            ->whereNull('duration')
            ->where('extension', $extension)
            ->first();

        if (!empty($currentCallUser)) {
            $currentCallUser->duration = $dateStart - $currentCallUser->start;
            $currentCallUser->save();

            if (!empty($currentCallUser->user_id)) {
                broadcast(new UpdateUserStatus($currentCallUser->user));

                startUserAdministrativeTime($currentCall, $currentCallUser->user);
            }
        }

        return $currentCallUser;
    }
}

if (!function_exists('startCurrentCallUser')) {
    /**
     * startCurrentCallUser Iniciamos el tramo de la llamada asignado a una extensión o un usuario.
     * @author Roger Corominas
     *
     * @param  App\CurrentCall $currentCall
     * @param  String $extension
     * @param  Integer $dateStart
     * @return App\CurrentCallUser
     */
    function startCurrentCallUser($currentCall, $extension, $dateStart)
    {
        $currentCallUser = $currentCall->call_users()
            ->whereNull('duration')
            ->where('extension', $extension)
            ->first();

        if (empty($currentCallUser)) {
            $user = get_extension_user($currentCall->company_id, $extension);

            if (!empty($user)) {
                $userId = $user->id;
                $departmentId = $user->department_id;
            } else {
                $userId = null;
                $departmentId = null;
            }

            $currentCallUser = CurrentCallUser::create([
                'current_call_id' => $currentCall->id,
                'extension' => $extension,
                'start' => $dateStart,
                'user_id' => $userId,
                'department_id' => $departmentId
            ]);

            setCurrentCallUserCalledAnsweredByExntension($currentCall->id, $extension, 1);

            if (empty($currentCall->duration_wait)) {
                $currentCall->duration_wait = $dateStart - $currentCall->start;
            }

            if (!empty($currentCallUser->user_id)) {

                if (!empty($user->department_id)) {
                    $currentCall->department_id = $user->department_id;
                    $currentCall->save();
                }

                broadcast(new UpdateUserStatus($currentCallUser->user));

                //RC: Gestionamos el tiempo administrativo
                activeUserAdministrativeTime($currentCall, $currentCallUser->user);
            }
        }

        return $currentCallUser;
    }
}

if (!function_exists('finishChannel')) {
    /**
     * finishChannel Elimina el registro del canal de la llamada
     * @author Roger Corominas
     *
     * @param  mixed $channel Nombre identificativo del canal
     * @return void
     */
    function finishChannel(String $channel)
    {
        $channel = PbxChannel::where('name', $channel)->first();

        if (!empty($channel)) {
            $channel->delete();
        }
    }
}

if (!function_exists('setCurrentCallUserCalledAnsweredByExntension')) {
    function setCurrentCallUserCalledAnsweredByExntension($currentCallId, $extension, $answered = 1)
    {
        //RC: Marcamos la llamada como contestada
        $current_call_user_called = CurrentCallUserCalled::where('current_call_id', $currentCallId)
            ->where('extension', $extension)
            ->orderBy('id', 'desc')
            ->first();

        if (!empty($current_call_user_called)) {
            $current_call_user_called->answered = $answered;
            $current_call_user_called->save();
        }
    }
}

if (!function_exists('getTrunkChannelFromCurrentCall')) {
    function getTrunkChannelFromCurrentCall($currentCall)
    {
        $trunk_channel_name = '';
        foreach ($currentCall->channels as $channel) {
            $channel_name = get_channel_name($channel->name);

            if (Trunk::where('name', $channel_name)->count() > 0) {
                $trunk_channel_name = $channel->name;
                break;
            }
        }

        return $trunk_channel_name;
    }
}

if (!function_exists('activeUserAdministrativeTime')) {
    function activeUserAdministrativeTime($currentCall, $user)
    {
        if (empty($currentCall->campaign_id)) {
            $administrativeTime = getAdministrativeTimeFromUser($user);

            if (!empty($administrativeTime) && !empty($user->extension)) {
                $res = pause_all_extension($user->extension);

                //if (empty($res['error'])) {
                    $callUserAdministrativeTime = new CallUserAdministrativeTime();
                    $callUserAdministrativeTime->call_id = $currentCall->id;
                    $callUserAdministrativeTime->user_id = $user->id;
                    $callUserAdministrativeTime->call_type = 'current_call';
                    $callUserAdministrativeTime->duration = $administrativeTime;
                    $callUserAdministrativeTime->is_started = 0;
                    $callUserAdministrativeTime->save();
                //}
            }
        } else {
            $campaign = $currentCall->campaign;

            if ($currentCall->call_type_id == 1) {
                if (!empty($campaign->campaign_in_call->administrative_time)) {
                    $administrativeTime = $campaign->campaign_in_call->administrative_time;
                }
            } else if ($currentCall->call_type_id == 2) {
                if (!empty($campaign->campaign_out_call->administrative_time)) {
                    $administrativeTime = $campaign->campaign_out_call->administrative_time;
                }
            }

            if (!empty($administrativeTime) && !empty($user->extension)) {
                $res = pause_all_extension($user->extension);

                //if (empty($res['error'])) {
                $callUserAdministrativeTime = new CallUserAdministrativeTime();
                $callUserAdministrativeTime->call_id = $currentCall->id;
                $callUserAdministrativeTime->user_id = $user->id;
                $callUserAdministrativeTime->call_type = 'current_call';
                $callUserAdministrativeTime->duration = $administrativeTime;
                $callUserAdministrativeTime->is_started = 0;
                $callUserAdministrativeTime->save();
                //}
            }
        }
    }
}

if (!function_exists('startUserAdministrativeTime')) {
    function startUserAdministrativeTime($currentCall, $user)
    {
        $callUserAdministrativeTime = CallUserAdministrativeTime::where('call_id', $currentCall->id)
            ->where('call_type', 'current_call')
            ->where('user_id', $user->id)
            ->where('is_started', '0')
            ->first();

        if (!empty($callUserAdministrativeTime)) {

            if ($currentCall->call_type_id == 3) {
                $callUserAdministrativeTime->delete();
            } else {
                FinishUserAdministrativeTime::dispatch($callUserAdministrativeTime->id)
                    ->delay(now()->addSeconds($callUserAdministrativeTime->duration));

                $callUserAdministrativeTime->is_started = 1;
                $callUserAdministrativeTime->save();
            }
        }
    }
}