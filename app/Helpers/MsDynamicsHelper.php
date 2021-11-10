<?php

use App\Account;
use App\AccountContactType;
use App\Call;
use App\CallExternalCode;
use App\CallUserExternalCode;
use App\CompanyConfig;
use App\User;

if(!function_exists('ms_dynamics_generate_account_links')) {
    function ms_dynamics_generate_account_links($phone)
    {
        try {
            $url = [];
            $urlAccountsReg = CompanyConfig::where('key', 'msdynamics_api_account_url')->first();
            if (!empty($urlAccountsReg) && !empty($urlAccountsReg->value)) {
                $account_ids = ms_dynamics_search_account_id_by_phone($phone);
                foreach($account_ids as $account_id) {
                    $url[] = str_replace('##ID##', $account_id, $urlAccountsReg->value);
                }
            }

            return $url;
        } catch (Exception $e) {
            return [];
        }
    }
}
if(!function_exists('ms_dynamics_search_account_id_by_phone')) {
    function ms_dynamics_search_account_id_by_phone($phone) {
        try {
            $serviceUrlReg = CompanyConfig::where('key', 'msdynamics_api_resource')->first();
            $tokenReg = CompanyConfig::where('key', 'msdynamics_api_token')->first();

            $account_ids = [];
            $phone_no_country_code = clean_country_code($phone);
            $phone_country_code = plus_country_code($phone);
            $curl = curl_init();

            $url = $serviceUrlReg->value.'accounts?';
            $url .= '$select=name,telephone1,telephone2,telephone3%0A&$top=10';
            $url .= '&$filter=telephone1%20eq%20%27' . $phone_no_country_code . '%27%20or%20telephone2%20eq%20%27' . $phone_no_country_code . '%27%20or%20telephone3%20eq%20%27' . $phone_no_country_code . '%27%20or%20telephone1%20eq%20%27' . $phone_country_code . '%27%20or%20telephone2%20eq%20%27' . $phone_country_code . '%27%20or%20telephone3%20eq%20%27' . $phone_country_code . '%27';

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer ".$tokenReg->value,
                    "cache-control: no-cache"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                echo "cURL Error #:" . $err;
            } else {
                $var = json_decode($response);
                if (!empty($var) && !empty($var->value)) {
                    foreach ($var->value as $v) {
                        $account_ids[] = $v->accountid;
                    }

                    //RC: Si tenemos solo una cuenta la añadimos en la agenda de global call
                    if (count($var->value) == 1) {
                        $account = Account::where('company_id', 1)
                            ->where('code', $var->value[0]->accountid)
                            ->first();

                        if (empty($account)) {
                            $account = new Account();
                            $account->company_id = 1;
                            $account->code = $var->value[0]->accountid;
                            $account->name = $var->value[0]->name;
                            $account->save();

                            if (!empty($var->value[0]->telephone1)) {
                                $accountContactType = new AccountContactType();
                                $accountContactType->account_id = $account->id;
                                $accountContactType->contact_type_id = 1;
                                $accountContactType->value = $var->value[0]->telephone1;
                                $accountContactType->save();
                            }

                            if (!empty($var->value[0]->telephone2)) {
                                $accountContactType = new AccountContactType();
                                $accountContactType->account_id = $account->id;
                                $accountContactType->contact_type_id = 1;
                                $accountContactType->value = $var->value[0]->telephone2;
                                $accountContactType->save();
                            }

                            if (!empty($var->value[0]->telephone3)) {
                                $accountContactType = new AccountContactType();
                                $accountContactType->account_id = $account->id;
                                $accountContactType->contact_type_id = 1;
                                $accountContactType->value = $var->value[0]->telephone3;
                                $accountContactType->save();
                            }
                        }
                    }
                }
            }

            return $account_ids;
        } catch (Exception $e) {
            dd($e);
            return [];
        }
    }
}

if (!function_exists('ms_dynamics_set_account_call')) {
    function ms_dynamics_set_account_call($call)
    {
        if ($call->call_type_id == 1) {
            $account_ids = ms_dynamics_search_account_id_by_phone($call->from);
        } else {
            $account_ids = ms_dynamics_search_account_id_by_phone($call->to);

            if (empty($account_ids)) {
                $account_ids = ms_dynamics_search_account_id_by_phone(substr($call->to, 1));
            }
        }

        if (!empty($account_ids)) {
            if (count($account_ids) == 1) {

                $call->account_id = $account_ids[0];
                $call->save();
            }
        }

        return $call;
    }
}

if (!function_exists('ms_dynamics_get_usercode_by_email')) {
    function ms_dynamics_get_usercode_by_email($email)
    {
        try {
            $serviceUrlReg = CompanyConfig::where('key', 'msdynamics_api_resource')->first();
            $tokenReg = CompanyConfig::where('key', 'msdynamics_api_token')->first();

            $user_ids = [];
            $curl = curl_init();

            $url = $serviceUrlReg->value . 'systemusers?';
            $url .= '$select=systemuserid%0A&$top=10';
            $url .= '&$filter=internalemailaddress%20eq%20%27' . $email . '%27';

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer " . $tokenReg->value,
                    "cache-control: no-cache"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                echo "cURL Error #:" . $err;
            } else {
                $var = json_decode($response);
                foreach ($var->value as $v) {
                    $user_ids[] = $v->systemuserid;
                }
                return $user_ids;
            }
        } catch (Exception $e) {
            return [];
        }
    }
}

if (!function_exists('ms_dynamics_create_custom_call')) {
    function ms_dynamics_create_custom_call($callId)
    {
        $call = Call::where('id', $callId)->first();
        $uuid = ms_dynamics_create_header_custom_call($call);

        if (!empty($uuid)) {
            foreach ($call->call_users as $call_user) {
                ms_dynamics_create_line_custom_call($uuid, $call, $call_user);
            }
        }
    }
}

if (!function_exists('ms_dynamics_create_header_custom_call')) {
    function ms_dynamics_create_header_custom_call($call)
    {

        if (!empty($call)) {
            if (empty($call->account_id)) {
                $call = ms_dynamics_set_account_call($call);
            }
            $data = ms_dynamics_set_data_header_custom_call($call);

            if (!empty($data)) {
                //RC: emitimos el evento

                try {
                    $serviceUrlReg = CompanyConfig::where('key', 'msdynamics_api_resource')->first();
                    $tokenReg = CompanyConfig::where('key', 'msdynamics_api_token')->first();

                    $curl = curl_init();

                    $url = $serviceUrlReg->value . 'crda0_llamadacentralitas';

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_HEADER => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => "POST",
                        CURLOPT_POSTFIELDS => json_encode($data),
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/json',
                            "Authorization: Bearer " . $tokenReg->value,
                            "cache-control: no-cache"
                        )
                    ));

                    $response = curl_exec($curl);
                    $err = curl_error($curl);

                    curl_close($curl);

                    if (!empty($response)) {
                        echo $response;
                        $lines = explode("\r\n", $response);

                        foreach ($lines as $line) {
                            $cols = explode(": ", $line);
                            if ($cols[0] == 'Location') {
                                $url = trim($cols[1]);
                                $start_uuid = strpos($url, '(');
                                $uuid = substr($url, $start_uuid + 1, -1);

                                echo "\r\n" . $uuid . "\r\n";
                                $call_external_call = new CallExternalCode();
                                $call_external_call->call_id = $call->id;
                                $call_external_call->code = $uuid;
                                $call_external_call->save();
                                return $uuid;
                                break;
                            }
                        }
                    }
                } catch (Exception $e) {
                    dd($e);
                }
            }
        }
    }
}

if (!function_exists('ms_dynamics_create_line_custom_call')) {
    function ms_dynamics_create_line_custom_call($uuid, $call, $call_user)
    {

        if (!empty($call)) {
            $data = ms_dynamics_set_data_line_custom_call($uuid, $call, $call_user);

            if (!empty($data)) {
                //RC: emitimos el evento
                try {
                    $serviceUrlReg = CompanyConfig::where('key', 'msdynamics_api_resource')->first();
                    $tokenReg = CompanyConfig::where('key', 'msdynamics_api_token')->first();

                    $curl = curl_init();

                    $url = $serviceUrlReg->value . 'crda0_llamadacentralitas';

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_HEADER => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => "POST",
                        CURLOPT_POSTFIELDS => json_encode($data),
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/json',
                            "Authorization: Bearer " . $tokenReg->value,
                            "cache-control: no-cache"
                        )
                    ));

                    $response = curl_exec($curl);
                    $err = curl_error($curl);

                    curl_close($curl);

                    if (!empty($response)) {
                        $lines = explode("\r\n", $response);

                        foreach ($lines as $line) {
                            $cols = explode(": ", $line);
                            if ($cols[0] == 'Location') {
                                $url = trim($cols[1]);
                                $start_uuid = strpos($url, '(');
                                $uuid = substr($url, $start_uuid + 1, -1);

                                echo "\r\n" . $uuid . "\r\n";
                                $call_user_external_call = new CallUserExternalCode();
                                $call_user_external_call->call_user_id = $call_user->id;
                                $call_user_external_call->code = $uuid;
                                $call_user_external_call->save();
                                return $uuid;
                                break;
                            }
                        }
                    }
                } catch (Exception $e) {
                    dd($e);
                }
            }
        }
    }
}

if (!function_exists('ms_dynamics_set_data_header_custom_call')) {
    function ms_dynamics_set_data_header_custom_call($call)
    {
        $start = date('Y-m-d', $call->start) . 'T' . date('H:i:s', $call->start) . 'Z';
        $end_time = strtotime('+' . $call->duration . 'second', $call->start);
        $end = date('Y-m-d', $end_time) . 'T' . date('H:i:s', $end_time) . 'Z';

        $data = [];
        $data['actualdurationminutes'] = 0;
        $data['actualend'] = $end;
        $data['actualstart'] = $start;
        if (!empty($call->department_id)) {
            $data['crda0_departamentocentralita'] = $call->department->code;
        }
        $data['crda0_duracionensegundos'] = $call->duration;
        $data['crda0_duracionespera'] = $call->duration_wait;
        $data['crda0_estado'] = $call->call_status->code;
        // $data['crda0_estado'] = '430580002';
        if (!empty($call->call_end_id)) {
            $data['crda0_final'] = $call->call_end->code;
            // $data['crda0_final'] = '430580051';
            $data['description'] = $call->call_end->name;
            $data['subject'] = $call->call_end->name;
        }
        $data['crda0_grabacion'] = false;
        $data['crda0_tipo'] = $call->call_type->code;
        // $data['crda0_tipo'] = '430580000';
        $data['scheduleddurationminutes'] = 0;
        $data['scheduledend'] = $end;
        $data['scheduledstart'] = $start;
        $data['statecode'] = 1;
        $data['crda0_numerodestino'] = $call->to;
        $data['crda0_numeroorigen'] = $call->from;

        //RC: Obtenemos el propietario de la llamada
        if ($call->call_users()->count() > 0) {
            $call_user = $call->call_users()->whereNotNull('user_id')->orderBy('id', 'desc')->first();

            if (!empty($call_user)) {
                $user = User::where('id', $call_user->user_id)->first();

                if (!empty($user) && empty($user->code) && !empty($user->email)) {
                    $user_ids = ms_dynamics_get_usercode_by_email($user->email);

                    if (count($user_ids) == 1) {
                        $user->code = $user_ids[0];
                        $user->save();
                    }
                }

                if (!empty($user) && !empty($user->code)) {
                    $data['ownerid@odata.bind'] = "/systemusers(" . $user->code . ")";
                }
            }
        }

        if (empty($data['ownerid@odata.bind'])) {
            $data['ownerid@odata.bind'] = "/teams(0cc543ef-7d41-e811-a94e-000d3ab9aac9)";
        }

        //RC: obtenemos la centa
        if (!empty($call->account_id) && empty($call->account->code)) {
            if ($call->call_type_id == 1) {
                ms_dynamics_search_account_id_by_phone($call->from);
            } else {
                ms_dynamics_search_account_id_by_phone($call->to);
            }
        }

        $account = null;
        if (!empty($call->account_id)) {
            $account = Account::where('id', $call->account_id)->first();
        }

        if (!empty($account) && !empty($account->code)) {
            $data['regardingobjectid_account@odata.bind'] = "/accounts(" . $account->code . ")";
        }

        return $data;
    }
}

if (!function_exists('ms_dynamics_set_data_line_custom_call')) {
    function ms_dynamics_set_data_line_custom_call($uuid, $call, $call_user)
    {
        $start = date('Y-m-d', $call_user->start) . 'T' . date('H:i:s', $call_user->start) . 'Z';
        $end_time = strtotime('+' . $call_user->duration . 'second', $call_user->start);
        $end = date('Y-m-d', $end_time) . 'T' . date('H:i:s', $end_time) . 'Z';


        $data = [];
        $data['crda0_Llamadacabecera_crda0_Llamadacentralita@odata.bind'] = '/crda0_llamadacentralitas(' . $uuid . ')';
        $data['actualend'] = $end;
        $data['actualstart'] = $start;
        if (!empty($call_user->user->department_id)) {
            $data['crda0_departamentocentralita'] = $call_user->user->department->code;
        }
        $data['crda0_duracionensegundos'] = $call_user->duration;
        $data['crda0_duracionespera'] = 0;
        $data['crda0_estado'] = $call->call_status->code;

        if (!empty($call->call_end_id)) {
            $data['crda0_final'] = $call->call_end->code;
            $data['description'] = $call->call_end->name;
            $data['subject'] = $call->call_end->name;
        }
        $data['crda0_grabacion'] = false;
        $data['crda0_tipo'] = $call->call_type->code;
        $data['scheduleddurationminutes'] = 0;
        $data['scheduledend'] = $end;
        $data['scheduledstart'] = $start;
        $data['statecode'] = 1;
        $data['crda0_numerodestino'] = $call->to;
        $data['crda0_numeroorigen'] = $call->from;
        $data['crda0_extension'] = $call_user->extension;

        //RC: Obtenemos el propietario de la llamada
        if (!empty($call_user->user_id)) {
            $user = User::where('id', $call_user->user_id)->first();

            if (!empty($user) && empty($user->code) && !empty($user->email)) {
                $user_ids = ms_dynamics_get_usercode_by_email($user->email);

                if (count($user_ids) == 1) {
                    $user->code = $user_ids[0];
                    $user->save();
                }
            }

            if (!empty($user) && !empty($user->code)) {
                $data['ownerid@odata.bind'] = "/systemusers(" . $user->code . ")";
            }
        }

        if (empty($data['ownerid@odata.bind'])) {
            $data['ownerid@odata.bind'] = "/teams(0cc543ef-7d41-e811-a94e-000d3ab9aac9)";
        }

        //RC: obtenemos la centa
        if (!empty($call->account_id) && empty($call->account->code)) {
            if ($call->call_type_id == 1) {
                ms_dynamics_search_account_id_by_phone($call->from);
            } else {
                ms_dynamics_search_account_id_by_phone($call->to);
            }
        }

        $account = null;
        if (!empty($call->account_id)) {
            $account = Account::where('id', $call->account_id)->first();
        }

        if (!empty($account) && !empty($account->code)) {
            $data['regardingobjectid_account@odata.bind'] = "/accounts(" . $account->code . ")";
        }

        return $data;
    }
}