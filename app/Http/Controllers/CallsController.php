<?php

namespace App\Http\Controllers;

use App\AccountContactType;
use App\Call;
use App\CallComment;
use App\CallEnd;
use App\CallLog;
use App\CallRecording;
use App\CallUser;
use App\CompanyConfig;
use App\Contact;
use App\CurrentCall;
use App\CurrentCallComment;
use App\CurrentCallIvr;
use App\CurrentCallLog;
use App\CurrentCallTransfer;
use App\CurrentCallUser;
use App\CurrentCallUserCalled;
use App\Department;
use App\Events\CallHangup as EventsCallHangup;
use App\Events\CallStart as EventsCallStart;
use App\Events\CallUpdate;
use App\Events\UpdateUserStatus;
use App\Extension;
use App\ListContactType;
use App\RouteIn;
use App\RouteOut;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Jobs\CurrentCallToCall as JobCurrentCallToCall;
use App\PbxBridge;
use App\PbxChannel;
use App\PbxChannelState;
use App\PhoneNumber;
use App\Trunk;
use App\UserSession;
use Illuminate\Support\Facades\Storage;

class CallsController extends Controller
{
    /**
     * @var int clave del módulo
     */
    private $module_key = 29;
    private $related_properties_current_call  = ['call_type', 'call_status', 'department', 'account', 'campaign', 'campaign_contact', 'active_call_users', 'call_users', 'call_users.user', 'call_user_calleds', 'call_user_calleds.user', 'call_comments', 'call_comments.user', 'call_logs', 'call_end', 'call_queues', 'account.tags', 'account.account_description'];
    private $related_properties  = ['call_type', 'call_status', 'department', 'account', 'campaign', 'campaign_contact', 'call_users', 'call_users.user', 'call_user_calleds', 'call_user_calleds.user', 'call_comments', 'call_comments.user', 'call_logs', 'call_end', 'call_queues', 'call_recordings', 'account.tags', 'account.account_description'];

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del cuenta indicado
     */
    private $module;

    public function apiGetCurrentCalls()
    {
        $user = get_loged_user();

        return CurrentCall::where('company_id', $user->company_id)
            ->whereNull('duration')
            ->get()->load($this->related_properties_current_call);
    }

    public function api_get_user_open_calls()
    {
        $user = get_loged_user();

        $company_config = CompanyConfig::where('company_id', $user->company_id)
            ->where('key', 'closecallswithoudendcall')
            ->first();

        if ($company_config && $company_config->value == '0') {
            /*$current_calls = CurrentCall::join('current_call_users', 'current_call_users.current_call_id', '=', 'current_calls.id')
            ->where('current_call_users.user_id', $user->id)
                ->select('current_calls.*')
                ->distinct()
                ->get()->load($this->related_properties_current_call);*/

            $calls = Call::join('call_users', 'call_users.call_id', '=', 'calls.id')
            ->where('call_users.user_id', $user->id)
                ->where('calls.call_type_id', '<>', '3')
                ->whereNull('calls.call_end_id')
                ->select('calls.*')
                ->distinct()
                ->get()->load($this->related_properties);

            return $calls;
        } else {
            $current_calls = CurrentCall::join('current_call_users', 'current_call_users.current_call_id', '=', 'current_calls.id')
            ->where('current_call_users.user_id', $user->id)
                ->select('current_calls.*')
                ->distinct()
                ->get()->load($this->related_properties_current_call);

            return $current_calls;
        }
    }

    public function api_actions_after_open_call($id) {
        $call = CurrentCall::where('id', $id)->first();
        $json['external_urls'] = [];

        if(!empty($call)) {
            if($call->call_type_id == 1) {
                $config_ms_dynamics = CompanyConfig::where('key', 'msdynamics_enable')->first();
                
                if (!empty($config_ms_dynamics) && $config_ms_dynamics->value == 1) {
                    $urls = ms_dynamics_generate_account_links($call->from);
                    if(!empty($urls)) {
                        foreach($urls as $url) {
                            $json['external_urls'][] = $url;
                        }
                    }
                }
            }
        }

        return $json;
    }

    public function api_get_report_call_ends($date = null, $department_id = null) {
        $loged_user = get_loged_user();
        if(empty($date)) {
            $date = date('Y-m-d');
        }

        $start = strtotime($date.' 00:00:00');
        $end = strtotime($date.' 23:59:59');

        if(empty($department_id)) {
            $results = DB::select("
                SELECT count(*) as total, SUM(duration) as duration, call_ends.name
                FROM calls 
                INNER JOIN call_ends ON calls.call_end_id = call_ends.id
                WHERE 
                calls.company_id = ".$loged_user->company_id."
                AND start >= ".$start." 
                AND start <= ".$end." 
                GROUP BY call_ends.name
                ORDER BY call_ends.name
            ");
        } else {
            $results = DB::select("
                SELECT count(*) as total, SUM(duration) as duration, call_ends.name
                FROM calls 
                INNER JOIN call_ends ON calls.call_end_id = call_ends.id
                WHERE 
                calls.company_id = ".$loged_user->company_id."
                AND calls.department_id = $department_id 
                AND start >= ".$start." 
                AND start <= ".$end." 
                GROUP BY call_ends.name
                ORDER BY call_ends.name
            ");
        }

        return $results;
    }

    public function api_get_report_route_in_calls($date = null, $department_id = null) {
        $loged_user = get_loged_user();
        if(empty($date)) {
            $date = date('Y-m-d');
        }

        $start = strtotime($date.' 00:00:00');
        $end = strtotime($date.' 23:59:59');

        if(empty($department_id)) {
            $results = DB::select("
                SELECT count(*) as total, route_ins.name 
                FROM calls 
                INNER JOIN route_ins ON calls.route_in_id = route_ins.id 
                WHERE 
                calls.company_id = ".$loged_user->company_id."
                AND start >= ".$start." 
                AND start <= ".$end." 
                GROUP BY route_ins.name
                ORDER BY route_ins.name
            ");
        } else {
            $results = DB::select("
                SELECT count(*) as total, route_ins.name 
                FROM calls 
                INNER JOIN route_ins ON calls.route_in_id = route_ins.id 
                WHERE 
                calls.company_id = ".$loged_user->company_id."
                AND start >= ".$start." 
                AND start <= ".$end." 
                AND calls.department_id = $department_id 
                GROUP BY route_ins.name
                ORDER BY route_ins.name
            ");
        }

        return $results;
    }

    public function api_get_report_route_in_lost_calls($date = null, $department_id = null) {
        $loged_user = get_loged_user();

        if(empty($date)) {
            $date = date('Y-m-d');
        }

        $start = strtotime($date.' 00:00:00');
        $end = strtotime($date.' 23:59:59');

        if(empty($department_id)) {
            $results = DB::select("
                SELECT count(*) as total, route_ins.name 
                FROM calls 
                INNER JOIN route_ins ON calls.route_in_id = route_ins.id 
                WHERE 
                calls.company_id = ".$loged_user->company_id."
                AND start >= ".$start." 
                AND start <= ".$end." 
                AND call_status_id = 4 
                GROUP BY route_ins.name 
                ORDER BY route_ins.name
            ");
        } else {
            $results = DB::select("
                SELECT count(*) as total, route_ins.name 
                FROM calls 
                INNER JOIN route_ins ON calls.route_in_id = route_ins.id 
                WHERE 
                calls.company_id = ".$loged_user->company_id."
                AND start >= ".$start." 
                AND start <= ".$end." 
                AND calls.department_id = $department_id 
                AND call_status_id = 4 
                GROUP BY route_ins.name 
                ORDER BY route_ins.name
            ");
        }

        return $results;
    }

    public function api_get_report_users_calls($date = null, $department_id = null) {
        $loged_user = get_loged_user();
        if(empty($date)) {
            $date = date('Y-m-d');
        }

        $start = strtotime($date.' 00:00:00');
        $end = strtotime($date.' 23:59:59');
        
        if(!empty($department_id)) {
            $users = User::join('user_company', 'users.id', '=', 'user_company.user_id')
                ->where('user_company.company_id', '=', $loged_user->company_id)
                ->where('department_id', $department_id)
                ->orderBy('users.name', 'ASC')
                ->get();
        } else {
            $users = User::join('user_company', 'users.id', '=', 'user_company.user_id')
                ->where('user_company.company_id', '=', $loged_user->company_id)
                ->orderBy('users.name', 'ASC')
                ->get();
        }

        $results = [];
        $i = 0;
        foreach($users as $user) {
            $ins = DB::select("
                SELECT COUNT(DISTINCT calls.id) as total, SUM(call_users.duration) as duration, call_users.user_id
                FROM calls 
                INNER JOIN call_users ON calls.id = call_users.call_id 
                WHERE calls.start >= ".$start." 
                AND calls.start <= ".$end." 
                AND user_id = ".$user->id." 
                AND call_type_id = 1 
                GROUP BY user_id
            ");
            
            $outs = DB::select("
                SELECT COUNT(DISTINCT calls.id) as total, SUM(call_users.duration) as duration , call_users.user_id
                FROM calls 
                INNER JOIN call_users ON calls.id = call_users.call_id 
                WHERE calls.start >= ".$start." 
                AND calls.start <= ".$end." 
                AND user_id = ".$user->id." 
                AND call_type_id = 2 
                GROUP BY user_id
            ");

            $calls_without_ends = DB::select("
                SELECT COUNT(DISTINCT calls.id) as total, call_users.user_id 
                FROM calls 
                INNER JOIN call_users ON calls.id = call_users.call_id 
                WHERE calls.start >= ".$start." 
                AND calls.start <= ".$end." 
                AND user_id = ".$user->id." 
                AND call_type_id IN (1,2) 
                AND call_end_id IS NULL 
                GROUP BY user_id
            ");

            $calls_losts = DB::select("
                SELECT COUNT(DISTINCT calls.id) as total, call_user_calleds.user_id 
                FROM calls 
                INNER JOIN call_user_calleds ON calls.id = call_user_calleds.call_id 
                WHERE calls.start >= ".$start." 
                AND calls.start <= ".$end." 
                AND call_user_calleds.user_id = ".$user->id." 
                AND call_type_id = 1
                GROUP BY user_id
            ");

            $results[$i]['user'] = $user->name;
            if(!empty($ins)) {
                $results[$i]['in']['total'] = $ins[0]->total;
                $results[$i]['in']['duration'] = $ins[0]->duration;
            } else {
                $results[$i]['in']['total'] = 0;
                $results[$i]['in']['duration'] = 0;
            }

            if(!empty($outs)) {
                $results[$i]['out']['total'] = $outs[0]->total;
                $results[$i]['out']['duration'] = $outs[0]->duration;
            } else {
                $results[$i]['out']['total'] = 0;
                $results[$i]['out']['duration'] = 0;
            }
            
            if(!empty($calls_without_ends)) {
                $results[$i]['without_call_end'] = $calls_without_ends[0]->total;
            } else {
                $results[$i]['without_call_end'] = 0;
            }

            if(!empty($calls_losts)) {
                $results[$i]['lost'] = $calls_losts[0]->total - $results[$i]['in']['total'];
                if($results[$i]['lost'] < 0) {
                    $results[$i]['lost'] = 0;
                }
            } else {
                $results[$i]['lost'] = 0;
            }
            $i++;
        }

        return $results;
    }

    public function api_resume_today_calls(Request $request) {
        $user = get_loged_user();
        $start = strtotime(date('Y-m-d').' 00:00:00');
        $end = strtotime(date('Y-m-d').' 23:59:59');

        $resume_calls = Call::where('start', '>=', $start)
            ->where('start', '<=', $end)
            ->groupBy('department_id', 'call_type_id')
            ->selectRaw('department_id, call_type_id, count(*) as total')
            ->get();
        
        $json[0]['in'] = 0;
        $json[0]['out'] = 0;
        $json[0]['internal'] = 0;

        $departments = Department::where('company_id', $user->company_id)
            ->get();

        foreach($departments as $department) {
            $json[$department->id]['in'] = 0;
            $json[$department->id]['out'] = 0;
            $json[$department->id]['internal'] = 0;
        }

        foreach($resume_calls as $resume_call) {
            switch($resume_call->call_type_id) {
                case 1:
                    if(empty($resume_call->department_id)) {
                        $json[0]['in'] = $resume_call->total;
                    } else {
                        $json[$resume_call->department_id]['in'] = $resume_call->total;
                    }
                break;
                case 2:
                    if(empty($resume_call->department_id)) {
                        $json[0]['out'] = $resume_call->total;
                    } else {
                        $json[$resume_call->department_id]['out'] = $resume_call->total;
                    }
                break;
                case 3:
                    if(empty($resume_call->department_id)) {
                        $json[0]['internal'] = $resume_call->total;
                    } else {
                        $json[$resume_call->department_id]['internal'] = $resume_call->total;
                    }
                break;
            }
        }

        return $json;
    }

    public function api_search(Request $request, $page = 0) {
        $this->module = get_user_module_security($this->module_key);
        $user = get_loged_user();

        if(!empty($request->start)) {
            $start = strtotime($request->start.' 00:00:00');
        } else {
            $start = strtotime(date('Y-m-d').' 00:00:00');
        }

        if(!empty($request->end)) {
            $end = strtotime($request->end.' 23:59:59');
        } else {
            $end = strtotime(date('Y-m-d').' 23:59:59');
        }

        $calls = Call::leftJoin('call_users', 'call_users.call_id', '=', 'calls.id')
            ->where('calls.company_id', $user->company_id)
            ->where('calls.start', '>=', $start)
            ->where('calls.start', '<=', $end)
            ->distinct('calls.id')
            ->select('calls.*');

        if (!empty($request->campaign_id)) {
            $calls->where('calls.campaign_id', $request->campaign_id);
        }
        if (!empty($request->campaign_contact_id)) {
            $calls->where('calls.campaign_contact_id', $request->campaign_contact_id);
        }
        if(!empty($request->call_type_id)) {
            $calls->where('calls.call_type_id', $request->call_type_id);
        }

        if(!empty($request->call_status_id)) {
            $calls->where('calls.call_status_id', $request->call_status_id);
        }

        if(!empty($request->call_end_id)) {
            $calls->where('calls.call_end_id', $request->call_end_id);
        }

        if(!empty($request->department_id)) {
            $calls->where('calls.department_id', $request->department_id);
        }

        if(!empty($request->department_id)) {
            $calls->where('calls.department_id', $request->department_id);
        }

        if(!empty($request->from)) {
            $calls->where('calls.from', 'like', '%'.$request->from.'%');
        }

        if(!empty($request->to)) {
            $calls->where('calls.to', 'like', '%'.$request->to.'%');
        }

        if (!empty($request->account_id)) {
            $calls->where('calls.account_id', 'like', '%' . $request->account_id . '%');
        } elseif (!empty($request->account)) {
            $filter = $request->account;
            $calls->leftJoin('accounts', 'accounts.id', '=', 'calls.account_id')
                ->where(function($query) use($filter) {
                    $query->orWhere('accounts.name', 'like', '%'.$filter.'%')
                ->orWhere('accounts.code', 'like', '%' . $filter . '%');
                });
        }

        if(!empty($request->user_id)) {
            $calls->where('call_users.user_id', $request->user_id);
        } elseif($user->user_type_id >= 3) {
            $calls->where('call_users.user_id', $user->id);
        }

        $limit = $request->limit;
        $limit_start = ($page -1) * $limit;

        if(!empty($request->sortColumn)) {
            $sortColumn = $request->sortColumn;
        } else {
            $sortColumn = 'start';
        }
        if($request->sortDirection == 1) {
            $sortDirection = 'asc';
        } else {
            $sortDirection = 'desc';
        }

        $json['page'] = (int)$page;
        $json['limit'] = $limit;
        $json['limit_start'] = $limit_start;
        $json['total'] = $calls->count('calls.id');
        $json['total_pages'] = ceil($json['total'] / $limit);
        $json['data'] = $calls
            ->orderBy($sortColumn, $sortDirection)
            ->limit($limit)
            ->offset($limit_start)
            ->get()
            ->load($this->related_properties);

        return $json;

    }

    public function api_make_call(Request $request) {
        $user = get_loged_user();

        $api_host = env('PBX_HOST', '');
        $api_port = env('PBX_PORT', '');

        if($user->extension) {
            $data['phone'] = $request->phone;
            $data['extension'] = $user->extension;

            if($api_host != '' && $api_port != '') {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_host.'/freePbxApi2/calls/make_call.php');
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
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_pickup_call(Request $request) {
        $user = get_loged_user();

        $api_host = env('PBX_HOST', '');
        $api_port = env('PBX_PORT', '');

        $response['extension'] = 100;
        return response()->json($response, 200);

        if($user->extension) {
            $data['phone'] = '*8#';
            $data['extension'] = $user->extension;

            if($api_host != '' && $api_port != '') {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_host.'/freePbxApi2/calls/make_call.php');
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
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_transfer_call(Request $request) {
        $user = get_loged_user();

        $api_host = env('PBX_HOST', '');
        $api_port = env('PBX_PORT', '');

        if($user->extension) {
            //RC: Buscamos la llamada activa del usuario
            $current_call_user = CurrentCallUser::where('extension', $user->extension)
                ->whereNull('duration')
                ->first();

            if (!empty($current_call_user)) {
                $current_call_transfer = new CurrentCallTransfer();
                $current_call_transfer->current_call_id = $current_call_user->current_call_id;
                $current_call_transfer->from = $user->extension;
                $current_call_transfer->to = $request->phone;
                $current_call_transfer->save();
            }

            $data['phone'] = $request->phone;
            $data['extension'] = $user->extension;

            if($api_host != '' && $api_port != '') {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_host.'/freePbxApi2/calls/transfer.php');
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
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_redirect_call(Request $request)
    {
        $user = get_loged_user();

        $api_host = env('PBX_HOST', '');
        $api_port = env('PBX_PORT', '');

        $response['error'] = true;
        $response['resp'] = '';

        if ($api_host != '' && $api_port != '') {
            if (!empty($request->current_call_id) && !empty($request->extension)) {
                $current_call = CurrentCall::findOrFail($request->current_call_id);

                if ($current_call->call_type_id == 1) {
                    $channel = $current_call->channels()->orderBy('id', 'ASC')->first();
                } else if ($current_call->call_type_id == 2) {
                    $channel = '';
                    foreach ($current_call->channels as $c) {
                        $name = self::get_channel_name($c->name);
                        $trunk = Trunk::where('name', $name)->first();
                        if (!empty($trunk)) {
                            $channel = $c;
                            break;
                        }
                    }
                } else {
                    $channel = '';
                }

                if (!empty($channel)) {
                    $data['extension'] = $request->extension;
                    $data['channel'] = $channel->name;

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $api_host . '/freePbxApi2/calls/redirect.php');
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
                }
            }
        }

        return response()->json($response, 200);
    }

    public function api_hangup_call(Request $request)
    {
        $user = get_loged_user();

        $api_host = env('PBX_HOST', '');
        $api_port = env('PBX_PORT', '');

        $response['error'] = true;
        $response['resp'] = '';

        if ($api_host != '' && $api_port != '') {
            if (!empty($request->current_call_id)) {
                $currentCall = CurrentCall::findOrFail($request->current_call_id);

                if ($currentCall->call_type_id != 3) {
                    $channelName = getTrunkChannelFromCurrentCall($currentCall);
                } else {
                    $channel = $currentCall->channels()->first();
                    if (!empty($channel)) {
                        $channelName = $channel->name;
                    }
                }

                if (!empty($channelName)) {
                    $data['channel'] = $channelName;

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $api_host . '/freePbxApi2/calls/hangup.php');
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
                }
            }
        }

        return response()->json($response, 200);
    }

    public function api_park_call(Request $request)
    {
        $user = get_loged_user();

        $api_host = env('PBX_HOST', '');
        $api_port = env('PBX_PORT', '');

        $response['error'] = true;
        $response['resp'] = '';

        if ($api_host != '' && $api_port != '') {
            if (!empty($request->current_call_id)) {
                $current_call = CurrentCall::findOrFail($request->current_call_id);

                if ($current_call->call_type_id == 1) {
                    $channel = $current_call->channels()->orderBy('id', 'ASC')->first();
                } else if ($current_call->call_type_id == 2) {
                    $channel = '';
                    foreach ($current_call->channels as $c) {
                        $name = self::get_channel_name($c->name);
                        $trunk = Trunk::where('name', $name)->first();
                        if (!empty($trunk)) {
                            $channel = $c;
                            break;
                        }
                    }
                } else {
                    $channel = '';
                }

                if (!empty($channel)) {
                    $data['channel'] = $channel->name;

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $api_host . '/freePbxApi2/calls/park.php');
                    curl_setopt($ch, CURLOPT_PORT, $api_port);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

                    if (!$resp = curl_exec($ch)) {
                        $response['error'] = true;
                        $response['resp'] = "error en la petición";
                    } else {
                        curl_close($ch);
                        $response = json_decode($resp);

                        $current_call->call_status_id = 6;
                        $current_call->save();

                        //RC: Miramos si tenemos algun usuario activo en la llamada
                        $old_current_call_user = CurrentCallUser::where('current_call_id', $current_call->id)
                        ->whereNull('duration')
                            ->first();

                        if (!empty($old_current_call_user)) {
                            $old_current_call_user->duration = strtotime('now') - $old_current_call_user->start;
                            $old_current_call_user->save();

                            if (!empty($old_current_call_user->user_id)) {
                                $old_user = User::findOrFail($old_current_call_user->user_id);
                                broadcast(new UpdateUserStatus($old_user));
                            }
                        }

                        //RC: Emitimos el evento de update call
                        $call_stat['id'] = $current_call->id;
                        $call_stat['from'] = $current_call->from;
                        $call_stat['to'] = $current_call->to;
                        $call_stat['start'] = $current_call->start;
                        $call_stat['duration'] = strtotime('now') - $current_call->start;
                        $call_stat['user_id'] = null;
                        $call_stat['department_id'] = $current_call->department_id;;
                        $call_stat['user_name'] = null;
                        $call_stat['extension'] = null;
                        $call_stat['queue'] = $current_call->queue;
                        $call_stat['call_type_id'] = $current_call->call_type_id;
                        $call_stat['call_status_id'] = $current_call->call_status_id;
                        $call_stat['event'] = 'park_call';

                        broadcast(new CallUpdate($call_stat, $current_call, $current_call->company_id));
                    }
                } else {
                    $response['resp'] = 'no tenemos canal';
                }
            } else {
                $response['resp'] = 'error en los parámetros';
            }
        } else {
            $response['resp'] = 'no tenemos pbx';
        }

        return response()->json($response, 200);
    }

    public function api_user_report() {
        $user = get_loged_user();
        $start = strtotime(date('Y-m-d'). ' 00:00:00');
        $end = strtotime(date('Y-m-d'). ' 23:59:59');
        
        $resume_call['in'] = 0;
        $resume_call['out'] = 0;
        $resume_call['internal'] = 0;
        $resume_call['lost'] = 0;
        $resume_call['last_calls'] = [];

        $currentCalls = CallUser::join('calls', 'calls.id', '=', 'call_users.call_id')
            ->where('user_id', $user->id)
            ->where('call_users.start', '>=', $start)
            ->where('call_users.start', '<=', $end)
            ->selectRaw("calls.call_type_id, count(*) as total")
            ->groupBy('calls.call_type_id')
            ->get();
        
        foreach($currentCalls as $currentCall) {
            switch($currentCall->call_type_id) {
                case 1:
                    $resume_call['in'] = $currentCall->total;
                break;
                case 2:
                    $resume_call['out'] = $currentCall->total;
                break;
                case 3:
                    $resume_call['internal'] = $currentCall->total;
                break;
            }
        }

        $resume_call['last_calls'] = Call::join('call_users', 'calls.id', '=', 'call_users.call_id')
            ->where('user_id', $user->id)
            ->where('call_users.start', '>=', $start)
            ->where('call_users.start', '<=', $end)
            ->select("calls.*")
            ->orderBy('calls.start', 'desc')
            ->limit('5')
            ->get()
            ->load($this->related_properties);

        return $resume_call;
    }

    public function api_set_call_end($id, $call_end_id) {
        $user = get_loged_user();
        $current_call = CurrentCall::where('id', $id)->first();
        $call_end = CallEnd::findOrFail($call_end_id);

        if(!empty($current_call) && $current_call->company_id == $user->company_id) {
            if($current_call->call_end_id != $call_end_id) {
                $current_call->call_end_id = $call_end_id;
                $current_call->save();

                $data_log['current_call_id'] = $current_call->id;
                $data_log['call_log_type_id'] = 12;
                $data_log['description'] = $user->name.': Asignamos el final: '.$call_end->name;
                $data_log['start'] = strtotime('now');
                CurrentCallLog::create($data_log);
            }

            return $current_call->load($this->related_properties_current_call);
        } else {
            $call_current_call = DB::select("SELECT * FROM call_current_call WHERE current_call_id = ".$id);
            if(!empty($call_current_call[0])) {
                $call = Call::where('id', $call_current_call[0]->call_id)->first();

                if(!empty($call)) {
                    if($call->call_end_id != $call_end_id) {
                        $call->call_end_id = $call_end_id;
                        $call->save();
        
                        $data_log['call_id'] = $call->id;
                        $data_log['call_log_type_id'] = 12;
                        $data_log['description'] = $user->name.': Asignamos el final: '.$call_end->name;
                        $data_log['start'] = strtotime('now');
                        CallLog::create($data_log);
                    }
        
                    return $call->load($this->related_properties);
                }
            } else {
                $call = Call::where('id', $id)->first();

                if(!empty($call)) {
                    if($call->call_end_id != $call_end_id) {
                        $call->call_end_id = $call_end_id;
                        $call->save();
        
                        $data_log['call_id'] = $call->id;
                        $data_log['call_log_type_id'] = 12;
                        $data_log['description'] = $user->name.': Asignamos el final: '.$call_end->name;
                        $data_log['start'] = strtotime('now');
                        CallLog::create($data_log);
                    }
        
                    return $call->load($this->related_properties);
                } else {
                    return [];
                }
            }
        }
    }

    public function api_add_comment(Request $request, $id) {
        $user = get_loged_user();
        $current_call = CurrentCall::where('id', $id)->first();

        if(!empty($current_call) && $current_call->company_id == $user->company_id) {
            $data['current_call_id'] = $current_call->id;
            $data['user_id'] = $user->id;
            $data['comment'] = nl2br($request->comment);
            CurrentCallComment::create($data);

            return $current_call->load($this->related_properties_current_call);
        } else {
            $call_current_call = DB::select("SELECT * FROM call_current_call WHERE current_call_id = ".$id);
            if(!empty($call_current_call[0])) {
                $call = Call::where('id', $call_current_call[0]->call_id)->first();

                if(!empty($call)) {
                    $data['call_id'] = $call->id;
                    $data['user_id'] = $user->id;
                    $data['comment'] = nl2br($request->comment);
                    CallComment::create($data);
        
                    return $call->load($this->related_properties);
                }
            } else {
                $call = Call::where('id', $id)->first();

                if(!empty($call)) {
                    $data['call_id'] = $call->id;
                    $data['user_id'] = $user->id;
                    $data['comment'] = nl2br($request->comment);
                    CallComment::create($data);
        
                    return $call->load($this->related_properties);
                } else {
                    return response()->json(['error' => 'unauthenticated'], 401);
                }
            }
        }
    }
    /**
     * ddi
     * callerid
     * start
     * call_type_id
     * linkedid
     * uniqueid
     * company_id
     */
    public function api_start_call(Request $request) {
        $data = $request->all();

        //RC: Miramos si tenemos una centralita multi-empresa para detectar de que empresa es
        if($data['company_id'] == -1) {
            $data['company_id'] = self::get_company_id_by_number($data['ddi']);
        }

        if(!empty($data['linkedid']) && !empty($data['uniqueid']) && !empty($data['company_id']) && !empty($data['call_type_id']) && !empty($data['callerid']) && !empty($data['ddi']) && !empty($data['start']) && $data['callerid'] != 'failed') {
            //RC: Miramos si tenemos la llamada ya registrada
            if($data['company_id'] == -1) {
                $current_call = CurrentCall::where('linkedid', $data['linkedid'])
                    ->first();
                
                if(!empty($current_call)) {
                    $data['company_id'] = $current_call->company_id;
                }
            } else {
                $current_call = CurrentCall::where('linkedid', $data['linkedid'])
                    ->where('company_id', $data['company_id'])
                    ->first();
            }

            if(empty($current_call)) {
                //RC: Miramos si es de un cotnacto o de una cuenta
                $account_contact_type = AccountContactType::join('accounts', 'accounts.id', '=', 'account_contact_types.account_id')
                    ->where('accounts.company_id', $data['company_id'])
                    ->where(function ($query) use ($data) {
                    $query->orWhere('account_contact_types.value', $data['callerid'])
                        ->orWhere('account_contact_types.value', str_replace('+', '00', $data['callerid']))
                        ->orWhere('account_contact_types.value', substr($data['callerid'], 1));
                })
                ->select('account_contact_types.*')
                    ->first();

                if (!empty($account_contact_type)) {
                    $account_id = $account_contact_type->account_id;
                } else {
                    $account_id = null;
                }

                //RC: Generamos el registro
                $data_save['company_id'] = $data['company_id'];
                $data_save['call_type_id'] = $data['call_type_id'];
                $data_save['call_status_id'] = 1;
                $data_save['account_id'] = $account_id;
                $data_save['uniqueid'] = $data['uniqueid'];
                $data_save['linkedid'] = $data['linkedid'];
                if($data['call_type_id'] == 1) {
                    $data_save['from'] = $data['callerid'];
                    $data_save['to'] = $data['ddi'];
                } else {
                    $data_save['from'] = $data['ddi'];
                    $data_save['to'] = $data['callerid'];
                }
                $data_save['start'] = strtotime($data['start']);
                $current_call = CurrentCall::create($data_save);

                //RC: Guardamos un registro en el log
                $data_log['current_call_id'] = $current_call->id;
                $data_log['call_log_type_id'] = 1;
                $data_log['description'] = 'Inicio de la llamada';
                $data_log['start'] = strtotime($data['start']);
                CurrentCallLog::create($data_log);

                if($current_call->call_type_id == 3) {
                    //RC: Miramos si tenemos un usuario con esta extensión
                    $user = self::get_extension_user($data['company_id'], $data['ddi']);
                    
                    //RC: Guardamos el usuario nuevo
                    $data_save = [];
                    $data_save['current_call_id'] = $current_call->id;
                    if(!empty($user)) {
                        $data_save['user_id'] = $user->id;
                        $data_save['user_name'] = $user->id;
                        if(!empty($user->department_id)) {
                            $data_save['department_id'] = $user->department_id;
                            
                            $current_call->department_id = $user->department_id;
                            $current_call->save();
                        } else {
                            $data_save['department_id'] = null;
                        }
                    }
                    $data_save['extension'] = $data['ddi'];
                    $data_save['start'] = strtotime($data['start']);
                    CurrentCallUser::create($data_save);

                    if(!empty($user)) {
                        broadcast(new UpdateUserStatus($user));
                    }

                    //RC: Asignamos la llamada como activa
                    $current_call->call_status_id = 2;
                    $current_call->save();

                    //RC: Generamos el registro del log
                    $data_log['current_call_id'] = $current_call->id;
                    $data_log['call_log_type_id'] = 6;
                    $data_log['description'] = 'Conectamos con '.(!empty($user) ? $user->name.' ' : '').'('.$data['ddi'].')';
                    $data_log['start'] = strtotime($data['start']);
                    CurrentCallLog::create($data_log);
                }

                //RC: Emitimos el evento de nueva llamada
                $call_stat['id'] = $current_call->id;
                $call_stat['from'] = $current_call->from;
                $call_stat['to'] = $current_call->to;
                $call_stat['start'] = $current_call->start;
                $call_stat['duration'] = strtotime('now') - $current_call->start;
                if($current_call->call_type_id == 3) {
                    if(!empty($user)) {
                        $call_stat['user_id'] = $user->id;
                        $call_stat['user_name'] = $user->name;
                        if(!empty($user->department_id)) {
                            $call_stat['department_id'] = $user->department_id;
                        } else {
                            $call_stat['department_id'] = null;
                        }
                    } else {
                        $call_stat['user_id'] = null;
                        $call_stat['user_name'] = null;
                        $call_stat['department_id'] = null;
                    }
                    $call_stat['extension'] = $current_call->ddi;
                } else {
                    $call_stat['user_id'] = null;
                    $call_stat['user_name'] = null;
                    $call_stat['department_id'] = null;
                    $call_stat['extension'] = $current_call->ddi;
                }
                $call_stat['queue'] = null;
                $call_stat['call_type_id'] = $current_call->call_type_id;
                $call_stat['call_status_id'] = $current_call->call_status_id;

                //RC: Asignamos el canal
                PbxChannel::where('uniqueid', $current_call->uniqueid)
                    ->where('linkedid', $current_call->linkedid)
                    ->update([
                        'current_call_id' => $current_call->id
                    ]);

                broadcast(new EventsCallStart($call_stat, $current_call, $current_call->company_id));

            } else if($current_call->call_type_id == 3 && $data['call_type_id'] == 2) {
                $current_call->call_type_id = 2;
                $current_call->save();

                //RC: Emitimos el evento de nueva llamada
                $call_stat['id'] = $current_call->id;
                $call_stat['from'] = $current_call->from;
                $call_stat['to'] = $current_call->to;
                $call_stat['start'] = $current_call->start;
                $call_stat['duration'] = strtotime('now') - $current_call->start;
                if ($current_call->call_users()->whereNull('duration')->count() > 0) {
                    $current_call_user = $current_call->call_users()->whereNull('duration')->first();
                    if ($current_call_user->user_id) {
                        $call_stat['user_id'] = $current_call_user->user_id;
                        $call_stat['user_name'] = $current_call_user->user->name;

                        if (!empty($user)) {
                            broadcast(new UpdateUserStatus($current_call_user->user));
                        }
                    }
                    $call_stat['department_id'] = $current_call->department_id;;
                    $call_stat['extension'] = $current_call_user->extension;
                } else {
                    $call_stat['user_id'] = null;
                    $call_stat['user_name'] = null;
                    $call_stat['department_id'] = $current_call->department_id;;
                    $call_stat['extension'] = null;
                }
                $call_stat['queue'] = null;
                $call_stat['event'] = 'external_call';
                $call_stat['call_type_id'] = $current_call->call_type_id;
                $call_stat['call_status_id'] = $current_call->call_status_id;

                broadcast(new CallUpdate($call_stat, $current_call, $current_call->company_id));

            }
        }
    }

    /**
     * queue
     * start
     * linkedid
     * uniqueid
     * company_id
     */
    public function api_set_callerid(Request $request) {
        $data = $request->all();
        if(!empty($data['company_id']) && !empty($data['linkedid']) && !empty($data['from']) && !empty($data['to']) && !empty($data['start'])) {
            //RC: Miramos si tenemos la llamada
            if($data['company_id'] == -1) {
                $current_call = CurrentCall::where('linkedid', $data['linkedid'])
                    ->first();
                
                if(!empty($current_call)) {
                    $data['company_id'] = $current_call->company_id;
                }
            } else {
                $current_call = CurrentCall::where('linkedid', $data['linkedid'])
                    ->where('company_id', $data['company_id'])
                    ->first();
            }

            if(!empty($current_call) && $current_call->call_type_id >= 2) {
                if($current_call->from  != $data['from'] || $current_call->to  != $data['to']) {
                    //RC: Miramos si es de un cotnacto o de una cuenta
                    $account_contact_type = AccountContactType::join('accounts', 'accounts.id', '=', 'account_contact_types.account_id')
                        ->where('company_id', $current_call->company_id)
                        ->where(function ($query) use ($data) {
                            $query->orWhere('value', $data['to'])
                                ->orWhere('value', str_replace('+', '00', $data['to']))
                        ->orWhere('value', substr($data['to'], 1));
                    })
                        ->select('account_contact_types.*')
                        ->first();

                    if (!empty($account_contact_type)) {
                        $account_id = $account_contact_type->account_id;
                    } else {
                        $account_id = null;
                    }

                    /*$account_id = null;
                    $contact_id = null;
                    if(!empty($list_contact_type)) {
                        if($list_contact_type->module_key == 9) {
                            $account_id = $list_contact_type->reference_id;
                        } elseif($list_contact_type->module_key == 7) {
                            $contact = Contact::where('id', $list_contact_type->reference_id)->first();
                            $contact_id = $contact->id;
                            if(!empty($contact->account_id)) {
                                $account_id = $contact->account_id;
                            }
                        }
                    }*/

                    $current_call->from = $data['from'];
                    $current_call->to = $data['to'];
                    if(empty($current_call->account_id)) {
                        $current_call->account_id = $account_id;
                    }
                    $current_call->save();

                    //RC: Guardamos un registro en el log
                    $data_log['current_call_id'] = $current_call->id;
                    $data_log['call_log_type_id'] = 11;
                    $data_log['description'] = 'Modificamos la información: '.$data['from'].' -> '.$data['to'];
                    $data_log['start'] = strtotime($data['start']);
                    CurrentCallLog::create($data_log);

                    //RC: Emitimos el evento de update call
                    $call_stat['id'] = $current_call->id;
                    $call_stat['from'] = $current_call->from;
                    $call_stat['to'] = $current_call->to;
                    $call_stat['start'] = $current_call->start;
                    $call_stat['duration'] = strtotime('now') - $current_call->start;
                    if($current_call->call_users()->whereNull('duration')->count() > 0) {
                        $current_call_user = $current_call->call_users()->whereNull('duration')->first();
                        if($current_call_user->user_id) {
                            $call_stat['user_id'] = $current_call_user->user_id;
                            $call_stat['user_name'] = $current_call_user->user->name;

                            if(!empty($user)) {
                                broadcast(new UpdateUserStatus($current_call_user->user));
                            }
                        }
                        $call_stat['department_id'] = $current_call->department_id;;
                        $call_stat['extension'] = $current_call_user->extension;
                    } else {
                        $call_stat['user_id'] = null;
                        $call_stat['user_name'] = null;
                        $call_stat['department_id'] = $current_call->department_id;;
                        $call_stat['extension'] = null;
                    }
                    $call_stat['queue'] = null;
                    $call_stat['call_type_id'] = $current_call->call_type_id;
                    $call_stat['call_status_id'] = $current_call->call_status_id;

                    broadcast(new CallUpdate($call_stat, $current_call, $current_call->company_id));
                }
            }
        }
    }

    /**
     * queue
     * position
     * start
     * linkedid
     * company_id
     */
    public function api_queue_join(Request $request) {
        $data = $request->all();
        if(!empty($data['company_id']) && !empty($data['linkedid']) && !empty($data['queue']) && !empty($data['start'])) {
            //RC: Miramos si tenemos la llamada
            if($data['company_id'] == -1) {
                $current_call = CurrentCall::where('linkedid', $data['linkedid'])
                    ->first();
                
                if(!empty($current_call)) {
                    $data['company_id'] = $current_call->company_id;
                }
            } else {
                $current_call = CurrentCall::where('linkedid', $data['linkedid'])
                    ->where('company_id', $data['company_id'])
                    ->first();
            }

            if(!empty($current_call)) {
                $current_call->queue = $data['queue'];
                $current_call->save();

                //RC: Guardamos un registro en el log
                $data_log['current_call_id'] = $current_call->id;
                $data_log['call_log_type_id'] = 2;
                $data_log['description'] = 'Entramos en la cola '.$data['queue'];
                $data_log['start'] = strtotime($data['start']);
                CurrentCallLog::create($data_log);

                //RC: Emitimos el evento de update call
                $call_stat['id'] = $current_call->id;
                $call_stat['from'] = $current_call->from;
                $call_stat['to'] = $current_call->to;
                $call_stat['start'] = $current_call->start;
                $call_stat['duration'] = strtotime('now') - $current_call->start;
                $call_stat['user_id'] = null;
                $call_stat['user_name'] = null;
                $call_stat['department_id'] = $current_call->department_id;;
                $call_stat['extension'] = null;
                $call_stat['queue'] = $current_call->queue;
                $call_stat['call_type_id'] = $current_call->call_type_id;
                $call_stat['call_status_id'] = $current_call->call_status_id;

                broadcast(new CallUpdate($call_stat, $current_call, $current_call->company_id));
            }
        }
    }

    /**
     * queue
     * extension
     * start
     * linkedid
     * company_id
     */
    public function api_agent_called(Request $request) {
        $data = $request->all();
        if(!empty($data['company_id']) && !empty($data['linkedid']) && !empty($data['extension']) && !empty($data['start'])) {
            //RC: Miramos si tenemos la llamada
            if($data['company_id'] == -1) {
                $current_call = CurrentCall::where('linkedid', $data['linkedid'])
                    ->first();
                
                if(!empty($current_call)) {
                    $data['company_id'] = $current_call->company_id;
                }
            } else {
                $current_call = CurrentCall::where('linkedid', $data['linkedid'])
                    ->where('company_id', $data['company_id'])
                    ->first();
            }

            if(!empty($current_call)) {
                //RC: Miramos si tenemos un usuario con esta extensión
                $user = self::get_extension_user($data['company_id'], $data['extension']);
                
                //RC: Generamos el registro
                $data_save['current_call_id'] = $current_call->id;
                if(!empty($user)) {
                    $data_save['user_id'] = $user->id;
                    if(!empty($user->department_id)) {
                        $data_save['department_id'] = $user->department_id;
                        $current_call->department_id = $user->department_id;
                        $current_call->save();
                    }
                }
                $data_save['extension'] = $data['extension'];
                $data_save['start'] = strtotime($data['start']);
                CurrentCallUserCalled::create($data_save);

                //RC: Generamos el registro del log
                $data_log['current_call_id'] = $current_call->id;
                $data_log['call_log_type_id'] = 5;
                $data_log['description'] = 'Suena la extensión '.(!empty($user) ? $user->name.' ' : '').'('.$data['extension'].')';
                $data_log['start'] = strtotime($data['start']);
                CurrentCallLog::create($data_log);

                //RC: Emitimos el evento de update call
                $call_stat['id'] = $current_call->id;
                $call_stat['from'] = $current_call->from;
                $call_stat['to'] = $current_call->to;
                $call_stat['start'] = $current_call->start;
                $call_stat['duration'] = strtotime('now') - $current_call->start;
                if(!empty($user)) {
                    $call_stat['user_id'] = $user->id;
                    if(!empty($user->department_id)) {
                        $call_stat['department_id'] = $user->department_id;
                    } else {
                        $call_stat['department_id'] = null;
                    }
                    $call_stat['user_name'] = $user->name;
                    $call_stat['extension'] = $data['extension'];
                } else {
                    $call_stat['user_id'] = null;
                    $call_stat['department_id'] = null;
                    $call_stat['user_name'] = $data['extension'];
                    $call_stat['extension'] = $data['extension'];
                }
                $call_stat['queue'] = $current_call->queue;
                $call_stat['call_type_id'] = $current_call->call_type_id;
                $call_stat['call_status_id'] = $current_call->call_status_id;

                broadcast(new CallUpdate($call_stat, $current_call, $current_call->company_id));

            }
        }
    }

    /**
     * queue
     * extension
     * start
     * linkedid
     * company_id
     */
    public function api_agent_ring_no_answer(Request $request) {
        $data = $request->all();
        if(!empty($data['company_id']) && !empty($data['linkedid']) && !empty($data['extension']) && !empty($data['start'])) {
            //RC: Miramos si tenemos la llamada
            if($data['company_id'] == -1) {
                $current_call = CurrentCall::where('linkedid', $data['linkedid'])
                    ->first();
                
                if(!empty($current_call)) {
                    $data['company_id'] = $current_call->company_id;
                }
            } else {
                $current_call = CurrentCall::where('linkedid', $data['linkedid'])
                    ->where('company_id', $data['company_id'])
                    ->first();
            }

            if(!empty($current_call)) {
                //RC: Miramos si tenemos un usuario con esta extensión
                $user = User::where('extension', $data['extension'])
                    ->where('company_id', $data['company_id'])
                    ->first();

                //RC: Emitimos el evento

            }
        }
    }

    /**
     * extension
     * start
     * callerid
     * linkedid
     * company_id
     */
    public function api_agent_connect(Request $request) {
        $data = $request->all();
        if(!empty($data['company_id']) && !empty($data['linkedid']) && !empty($data['extension']) && !empty($data['start'])) {
            //RC: Miramos si tenemos la llamada
            if($data['company_id'] == -1) {
                $current_call = CurrentCall::where('linkedid', $data['linkedid'])
                    ->first();
                
                if(!empty($current_call)) {
                    $data['company_id'] = $current_call->company_id;
                }
            } else {
                $current_call = CurrentCall::where('linkedid', $data['linkedid'])
                    ->where('company_id', $data['company_id'])
                    ->first();
            }

            if(!empty($current_call)) {
                if($current_call->call_type_id == 2 && $current_call->to === $data['extension']) {
                    exit;
                }

                //RC: Miramos si tenemos un usuario con esta extensión
                $user = self::get_extension_user($data['company_id'], $data['extension']);

                //RC: Miramos si el usuario tiene departamento lo tenemos que añadir
                if(!empty($user->department_id)) {
                    $current_call->department_id = $user->department_id;
                } else {
                    $current_call->department_id = null;
                }

                //RC: Miramos si tenemos la lista de espera
                if(empty($current_call->duration_wait)) {
                    $current_call->duration_wait = strtotime($data['start']) - $current_call->start;
                }

                $current_call->call_status_id = 2;
                $current_call->save();

                //RC: Miramos si tenemos algun usuario activo en la llamada
                $old_current_call_user = CurrentCallUser::where('current_call_id', $current_call->id)
                    ->whereNull('duration')
                    ->first();

                if(!empty($old_current_call_user)) {
                    if($old_current_call_user->extension == $data['extension']) {
                        exit;
                    }
                    $old_current_call_user->duration = strtotime($data['start']) - $old_current_call_user->start;
                    $old_current_call_user->save();

                    if(!empty($old_current_call_user->user_id)) {
                        $old_user = User::findOrFail($old_current_call_user->user_id);
                        broadcast(new UpdateUserStatus($old_user));
                    }
                }

                //RC: Guardamos el usuario nuevo
                $data_save['current_call_id'] = $current_call->id;
                if(!empty($user)) {
                    $data_save['user_id'] = $user->id;
                    if(!empty($user->department_id)) {
                        $data_save['department_id'] = $user->department_id;
                    } else {
                        $data_save['department_id'] = null;
                    }
                }
                $data_save['extension'] = $data['extension'];
                $data_save['start'] = strtotime($data['start']);
                CurrentCallUser::create($data_save);

                //RC: marcamos en los usuarios de la tabla user_calles como respondida
                if (!empty($data_save['user_id'])) {
                    $current_call_user_calleds = CurrentCallUserCalled::where('current_call_id', $current_call->id)
                        ->where('user_id', $data_save['user_id'])
                        ->orderBy('start', 'desc')
                        ->first();

                    if (!empty($current_call_user_calleds)) {
                        $current_call_user_calleds->answered = 1;
                        $current_call_user_calleds->save();
                    }
                } else {
                    //RC: Si no tenemos usuario lo miramos por la extensión
                    $current_call_user_calleds = CurrentCallUserCalled::where('current_call_id', $current_call->id)
                        ->where('extension', $data_save['extension'])
                        ->orderBy('start', 'desc')
                        ->first();

                    if (!empty($current_call_user_calleds)) {
                        $current_call_user_calleds->answered = 1;
                        $current_call_user_calleds->save();
                    }
                }

                if(!empty($user)) {
                    broadcast(new UpdateUserStatus($user));
                }

                //RC: Generamos el registro del log
                $data_log['current_call_id'] = $current_call->id;
                $data_log['call_log_type_id'] = 6;
                $data_log['description'] = 'Conectamos con '.(!empty($user) ? $user->name.' ' : '').'('.$data['extension'].')';
                $data_log['start'] = strtotime($data['start']);
                CurrentCallLog::create($data_log);

                //RC: Emitimos el evento de update call
                $call_stat['id'] = $current_call->id;
                $call_stat['from'] = $current_call->from;
                $call_stat['to'] = $current_call->to;
                $call_stat['start'] = $current_call->start;
                $call_stat['duration'] = strtotime('now') - $current_call->start;
                if(!empty($user)) {
                    $call_stat['user_id'] = $user->id;
                    $call_stat['user_name'] = $user->name;
                    $call_stat['extension'] = $data['extension'];
                    if(!empty($user->department_id)) {
                        $call_stat['department_id'] = $user->department_id;
                        $current_call->department_id = $user->department_id;
                        $current_call->save();
                    } else {
                        $call_stat['department_id'] = null;
                    }
                } else {
                    $call_stat['user_id'] = null;
                    $call_stat['department_id'] = $current_call->department_id;;
                    $call_stat['user_name'] = $data['extension'];
                    $call_stat['extension'] = $data['extension'];
                }
                $call_stat['queue'] = $current_call->queue;
                $call_stat['call_type_id'] = $current_call->call_type_id;
                $call_stat['call_status_id'] = $current_call->call_status_id;
                $call_stat['event'] = 'agent_connect';

                broadcast(new CallUpdate($call_stat, $current_call, $current_call->company_id));

            }
        }
    }

    /**
     * callerid
     * extension
     * start
     * cause
     * uniqueid
     * linkedid
     */

    public function api_set_ivr(Request $request) {
        $data = $request->all();
        if(!empty($data['company_id']) && !empty($data['linkedid']) && !empty($data['ivr']) && !empty($data['option']) && !empty($data['start'])) {
            //RC: Miramos si tenemos la llamada
            if($data['company_id'] == -1) {
                $current_call = CurrentCall::where('linkedid', $data['linkedid'])
                    ->first();
                
                if(!empty($current_call)) {
                    $data['company_id'] = $current_call->company_id;
                }
            } else {
                $current_call = CurrentCall::where('linkedid', $data['linkedid'])
                    ->where('company_id', $data['company_id'])
                    ->first();
            }

            if(!empty($current_call)) {
                $data_save['current_call_id'] = $current_call->id;
                $data_save['pbx_ivr'] = $data['ivr'];
                $data_save['option'] = $data['option'];
                $data_save['start'] = strtotime($data['start']);
                CurrentCallIvr::create($data_save);

                //RC: Guardamos un registro en el log
                $data_log['current_call_id'] = $current_call->id;
                $data_log['call_log_type_id'] = 3;
                $data_log['description'] = 'Entramos en el IVR '.$data['ivr'].' con la opción: '.$data['option'];
                $data_log['start'] = strtotime($data['start']);
                CurrentCallLog::create($data_log);

                //RC: Emitimos el evento de update call
                $call_stat['id'] = $current_call->id;
                $call_stat['from'] = $current_call->from;
                $call_stat['to'] = $current_call->to;
                $call_stat['start'] = $current_call->start;
                $call_stat['duration'] = strtotime('now') - $current_call->start;
                $call_stat['user_id'] = null;
                $call_stat['user_name'] = null;
                $call_stat['department_id'] = $current_call->department_id;
                $call_stat['extension'] = null;
                $call_stat['queue'] = null;
                $call_stat['call_type_id'] = $current_call->call_type_id;
                $call_stat['call_status_id'] = $current_call->call_status_id;

                broadcast(new CallUpdate($call_stat, $current_call, $current_call->company_id));
            }
        }
    }

    /**
     * 
     */
    public function api_set_voicemail(Request $request) {
        $data = $request->all();
        if(!empty($data['company_id']) && !empty($data['linkedid']) && !empty($data['start'])) {
            //RC: Miramos si tenemos la llamada
            if($data['company_id'] == -1) {
                $current_call = CurrentCall::where('linkedid', $data['linkedid'])
                    ->first();
                
                if(!empty($current_call)) {
                    $data['company_id'] = $current_call->company_id;
                }
            } else {
                $current_call = CurrentCall::where('linkedid', $data['linkedid'])
                    ->where('company_id', $data['company_id'])
                    ->first();
            }

            if(!empty($current_call)) {
                $current_call->call_status_id = 5;
                $current_call->save();

                $data_log['current_call_id'] = $current_call->id;
                $data_log['call_log_type_id'] = 9;
                $data_log['description'] = 'Llamada desviada al buzón.';
                $data_log['start'] = strtotime($data['start']);
                CurrentCallLog::create($data_log);
            }
        }
        exit;
    }

    /**
     * @author Roger Corominas
     * Hay un nuevo canal
     * @param Request $request objeto con todos los parámetros del evento
     */
    public function api_newchannel(Request $request)
    {
        $data = $request->all();

        if (!empty($data['Linkedid'])) {
            $current_call = CurrentCall::where('linkedid', $data['Linkedid'])
            ->first();

            if (empty($current_call)) {
                //RC: Si no tenemos una llamada con la tenemos que generar
                $current_call = self::create_call_from_ami_event($data);

                //RC Si no tenemos una llamada válida finalizamos el proceso con -1
                if (empty($current_call)) {
                    exit;
                }

                $call_event = 'EventsCallStart';
            } else {
                //RC: Si tenemos una llamada generada por un evento postario, la tenemos que completar.
                
                //RC: Si la llamada es de tipo interna y el canal es un troncal tenemos que marcala como llamada de salida
                if ($current_call->call_type_id == 3) {
                    $trunk_name = self::get_channel_name($data['Channel']);

                    //RC: Miramos si tenemos algun troncal con este nombre
                    $trunk = Trunk::whereRaw("LOWER(trunks.name) like '" . strtolower($trunk_name) . "'")
                        ->first();

                    if (!empty($trunk)) {
                        $current_call->call_type_id = 2;
                        $current_call->save();
                    }
                }
                $call_event = 'CallUpdate';
            }

            //RC: generamos el canal
            $pbx_channel = self::creat_pbx_channel_from_ami_event($data, $current_call->id);

            //RC: generamos el evento
            self::generate_event_call_start($current_call, $call_event);
        }

        exit;
    }

    public function api_hangup(Request $request) {
        $data = $request->all();

        if (!empty($data['Channel'])) {
            $channel = PbxChannel::where('name', $data['Channel'])->first();
            if (!empty($channel)) {
                $channel->delete();
            }
        }

        if (!empty($data['company_id']) && !empty($data['Linkedid']) && !empty($data['start'])) {
            //RC: Miramos si tenemos la llamada
            $current_call = CurrentCall::where('linkedid', $data['Linkedid'])
            ->first();

            if (!empty($current_call)) {
                //RC: Miramos si tenemos algun canal con esta llamada
                if (PbxChannel::where('current_call_id', $current_call->id)->count() == 0) {
                    //RC: Si no tenemos más canales tenemos que finalizar la llamada

                    //RC: Miramos si tenemos algun usuario activo en la llamada
                    $old_current_call_user = CurrentCallUser::where('current_call_id', $current_call->id)
                        ->whereNull('duration')
                        ->first();
                    if (!empty($old_current_call_user)) {
                        $old_current_call_user->duration = $data['start'] - $old_current_call_user->start;
                        $old_current_call_user->save();
                        $current_call->call_status_id = 3;

                        if (!empty($old_current_call_user->user_id)) {
                            $old_user = User::findOrFail($old_current_call_user->user_id);
                            broadcast(new UpdateUserStatus($old_user));
                        }
                    } else {
                        $old_current_call_user = CurrentCallUser::where('current_call_id', $current_call->id)
                            ->first();

                        if (!empty($old_current_call_user)) {
                            $current_call->call_status_id = 3;
                        } else  if ($current_call->call_status_id != 5) {
                            $current_call->call_status_id = 4;
                        }
                    }

                    $current_call->duration = $data['start'] - $current_call->start;
                    $current_call->save();

                    //RC: Generamos el registro del log
                    $data_log['current_call_id'] = $current_call->id;
                    $data_log['call_log_type_id'] = 10;
                    $data_log['description'] = 'Finalizamos la llamada';
                    $data_log['start'] = strtotime($data['start']);
                    CurrentCallLog::create($data_log);

                    //RC: Emitimos el evento de update call
                    $call_stat['id'] = $current_call->id;
                    $call_stat['from'] = $current_call->from;
                    $call_stat['to'] = $current_call->to;
                    $call_stat['start'] = $current_call->start;
                    $call_stat['duration'] = strtotime('now') - $current_call->start;
                    $call_stat['user_id'] = null;
                    $call_stat['user_name'] = null;
                    $call_stat['department_id'] = $current_call->department_id;;
                    $call_stat['extension'] = null;
                    $call_stat['queue'] = null;
                    $call_stat['call_type_id'] = $current_call->call_type_id;
                    $call_stat['call_status_id'] = $current_call->call_status_id;

                    broadcast(new EventsCallHangup($call_stat, $current_call, $current_call->company_id));

                    JobCurrentCallToCall::dispatch($current_call->id)->delay('5');
                } else {
                    $channel_prefix = self::get_channel_prefix($data['Channel']);
                    $channel_exten_name = self::get_channel_name($data['Channel']);
                    $extension = Extension::where('company_id', $current_call->company_id)->where('number', $channel_exten_name)->first();
                    if ($channel_prefix == 'PJSIP' && !empty($extension)) {
                        $current_call_user = $current_call->call_users()->where('extension', $extension->number)->whereNull('duration')->first();

                        if (!empty($current_call_user)) {
                            $current_call_user->duration = $data['start'] - $current_call_user->start;
                            $current_call_user->save();

                            if (!empty($current_call_user)) {
                                $old_user = User::where('id', $current_call_user->user_id)->first();
                                broadcast(new UpdateUserStatus($old_user));
                            }

                            $call_event = 'CallUpdate';
                            self::generate_event_call_start($current_call, $call_event);
                        }
                    }
                }
            }
        }

        exit;
    }

    public function api_dialbegin(Request $request)
    {
        $data = $request->all();

        if (!empty($data['Channel'])) {

            $pbx_channel = PbxChannel::where('name', $data['Channel'])->first();

            if (empty($pbx_channel)) {
                $pbx_channel = self::creat_pbx_channel_from_ami_event($data, null);
            }

            //RC: Si no tenemos canal lo tenemos que generar

            if (!empty($pbx_channel)) {

                //RC: Si no tenemos llamada actual la tenemos que generar
                $current_call = CurrentCall::where('linkedid', $data['Linkedid'])
                ->first();

                $pbx_channel_state = PbxChannelState::where('key', $data['ChannelState'])->first();

                if (empty($pbx_channel_state)) {
                    $pbx_channel_state = new PbxChannelState();
                    $pbx_channel_state->key = $data['ChannelState'];
                    $pbx_channel_state->name = $data['ChannelStateDesc'];
                    $pbx_channel_state->save();
                }

                $pbx_channel->pbx_channel_state_id = $pbx_channel_state->id;
                
                if (!empty($current_call)) {
                    $pbx_channel->current_call_id = $current_call->id;

                    if ($data['DestCallerIDNum'] != 's' && $current_call->call_type_id >= 2 && ($current_call->to == 's' || empty($current_call->to))) {
                        $current_call->to = $data['DestCallerIDNum'];

                        if (empty($current_call->account_id)) {

                            $account_contact_type = AccountContactType::join('accounts', 'accounts.id', '=', 'account_contact_types.account_id')
                            ->where('accounts.company_id', $data['company_id'])
                            ->where(function ($query) use ($data) {
                                $query->orWhere('account_contact_types.value', $data['DestCallerIDNum'])
                                    ->orWhere('account_contact_types.value', str_replace('+', '00', $data['DestCallerIDNum']))
                                    ->orWhere('account_contact_types.value', substr($data['DestCallerIDNum'], 1))
                                    ->orWhere('account_contact_types.value', '+34' . $data['DestCallerIDNum'])
                                    ->orWhere('account_contact_types.value', '+34' . substr($data['DestCallerIDNum'], 1))
                                    ->orWhereRaw('REPLACE(account_contact_types.value, "+", "00") = "' . substr($data['DestCallerIDNum'], 1) . '"')
                                    ->orWhereRaw('REPLACE(account_contact_types.value, "+", "00") = "' . $data['DestCallerIDNum'] . '"');
                            })
                                ->select('account_contact_types.*')
                                ->first();
                            if (!empty($account_contact_type)) {
                                $current_call->account_id = $account_contact_type->account_id;
                            }
                        }
                        $current_call->save();

                        $call_event = 'CallUpdate';
                        self::generate_event_call_start($current_call, $call_event);
                    }
                }
                $pbx_channel->save();
            }
        }

        exit;
    }

    public function api_dialend(Request $request)
    {
        $data = $request->all();

        //RC: Si no tenemos canal lo tenemos que generar

        if (!empty($data['Channel'])) {
            $pbx_channel = PbxChannel::where('name', $data['Channel'])->first();

            if (!empty($pbx_channel)) {
                //RC: Si no tenemos llamada la tenemosq ue generar

                $current_call = CurrentCall::where('linkedid', $data['Linkedid'])
                ->first();

                $pbx_channel_state = PbxChannelState::where('key', $data['ChannelState'])->first();

                if (empty($pbx_channel_state)) {
                    $pbx_channel_state = new PbxChannelState();
                    $pbx_channel_state->key = $data['ChannelState'];
                    $pbx_channel_state->name = $data['ChannelStateDesc'];
                    $pbx_channel_state->save();
                }

                $pbx_channel->pbx_channel_state_id = $pbx_channel_state->id;
                if (!empty($current_call)) {
                    $pbx_channel->current_call_id = $current_call->id;
                }
                $pbx_channel->save();

                if (!empty($current_call) && $data['DialStatus'] == 'ANSWER') {
                    $user = self::get_extension_user($current_call->company_id, $data['DestCallerIDNum']);
                    if (!empty($user)) {
                        $current_call->call_status_id = 2;
                        $current_call->save();

                        $current_call_user = $current_call->call_users()->where('extension', $data['DestCallerIDNum'])->whereNull('duration')->first();

                        if (empty($current_call_user)) {
                            $current_call_user = new CurrentCallUser();
                            $current_call_user->current_call_id = $current_call->id;
                            $current_call_user->start = $data['start'];
                            $current_call_user->extension = $user->extension;
                            $current_call_user->user_id = $user->id;
                            $current_call_user->save();
                        }
                        broadcast(new UpdateUserStatus($user));
                        

                        $call_event = 'CallUpdate';
                        self::generate_event_call_start($current_call, $call_event, 'agent_connect');
                    }
                }
            }
        }

        exit;
    }
    /**
     * Sin utilizar
     */
    public function api_bridgecreate(Request $request)
    {
        $data = $request->all();

        $pbx_bridge = new PbxBridge();
        $pbx_bridge->name = $data['BridgeUniqueid'];
        $pbx_bridge->save();

        exit;
    }

    public function api_bridgeenter(Request $request)
    {
        $data = $request->all();

        //RC: Si no tenemos bridge lo tenemos que generar
        $pbx_bridge = PbxBridge::where('name', $data['BridgeUniqueid'])->first();

        if (empty($pbx_bridge)) {
            $pbx_bridge = new PbxBridge();
            $pbx_bridge->name = $data['BridgeUniqueid'];
            $pbx_bridge->save();
        }

        //RC: Si no tenemos canal lo tenemos que generar
        $pbx_channel = PbxChannel::where('name', $data['Channel'])->first();
        if (!empty($pbx_channel) && !empty($pbx_bridge)) {
            $current_call = CurrentCall::where('linkedid', $data['Linkedid'])
            ->first();

            $pbx_channel_state = PbxChannelState::where('key', $data['ChannelState'])->first();

            if (empty($pbx_channel_state)) {
                $pbx_channel_state = new PbxChannelState();
                $pbx_channel_state->key = $data['ChannelState'];
                $pbx_channel_state->name = $data['ChannelStateDesc'];
                $pbx_channel_state->save();
            }

            $pbx_channel->pbx_channel_state_id = $pbx_channel_state->id;
            $pbx_channel->pbx_bridge_id = $pbx_bridge->id;
            
            if (!empty($current_call)) {
                $pbx_channel->current_call_id = $current_call->id;
                if ($current_call->to == 's') {

                    $account_contact_type = AccountContactType::join('accounts', 'accounts.id', '=', 'account_contact_types.account_id')
                        ->where('accounts.company_id', $current_call->company_id)
                        ->where(function ($query) use ($data) {
                            $query->orWhere('account_contact_types.value', $data['CallerIDNum'])
                                ->orWhere('account_contact_types.value', str_replace('+', '00', $data['CallerIDNum']))
                                ->orWhere('account_contact_types.value', substr($data['CallerIDNum'], 1));
                        })
                        ->select('account_contact_types.*')
                        ->first();

                    if (!empty($account_contact_type)) {
                        $current_call->account_id = $account_contact_type->account_id;
                    }

                    
                    $current_call->from = $data['ConnectedLineNum'];
                    $current_call->to = $data['CallerIDNum'];
                    
                    $current_call->save();

                    $call_event = 'CallUpdate';
                    self::generate_event_call_start($current_call, $call_event, 'agent_connect');
                }
            }
            $pbx_channel->save();

            $channel_prefix = self::get_channel_prefix($data['Channel']);
            $channel_exten_name = self::get_channel_name($data['Channel']);
            $extension = Extension::where('company_id', $current_call->company_id)->where('number', $channel_exten_name)->first();

            if (!empty($current_call) && $channel_prefix == 'PJSIP' && !empty($extension)) {
                $current_call_user = $current_call->call_users()->where('extension', $extension->number)->whereNull('duration')->first();

                if (empty($current_call_user)) {
                    $current_call->call_status_id = 2;
                    $current_call->save();
                    
                    $current_call_user = new CurrentCallUser();
                    $current_call_user->current_call_id = $current_call->id;
                    $current_call_user->start = $data['start'];
                    $current_call_user->extension = $extension->number;

                    $user = self::get_extension_user($current_call->company_id, $extension->number);

                    if (!empty($user)) {
                        $current_call_user->user_id = $user->id;
                    }

                    $current_call_user->save();

                    if (!empty($user)) {
                        broadcast(new UpdateUserStatus($user));
                    }

                    $call_event = 'CallUpdate';
                    self::generate_event_call_start($current_call, $call_event, 'agent_connect');
                }
            }
        }

        exit;
    }

    public function api_bridgeleave(Request $request)
    {
        $data = $request->all();

        //RC: Si no tenemos canal lo tenemos que generar

        $pbx_channel = PbxChannel::where('name', $data['Channel'])->first();
        if (!empty($pbx_channel)) {
            $bridge_id = $pbx_channel->pbx_bridge_id;
            $pbx_channel_state = PbxChannelState::where('key', $data['ChannelState'])->first();

            if (empty($pbx_channel_state)) {
                $pbx_channel_state = new PbxChannelState();
                $pbx_channel_state->key = $data['ChannelState'];
                $pbx_channel_state->name = $data['ChannelStateDesc'];
                $pbx_channel_state->save();
            }

            $pbx_channel->pbx_channel_state_id = $pbx_channel_state->id;
            $pbx_channel->pbx_bridge_id = null;
            $pbx_channel->save();
        }

        if (!empty($bridge_id)) {
            if (PbxChannel::where('pbx_bridge_id', $bridge_id)->count() == 0) {
                $pbx_bridge = PbxBridge::where('name', $bridge_id)->delete();
            }
        }

        exit;
    }

    /**
     * Si utilizar
     */
    public function api_bridgedestroy(Request $request)
    {
        $data = $request->all();


        $pbx_bridge = PbxBridge::where('name', $data['BridgeUniqueid'])->first();
        if (!empty($pbx_bridge)) {
            $pbx_bridge->delete();
        }

        exit;
    }

    /**
     * 
     */
    public function api_attended_transfer(Request $request) {

    }

    /**
     * 
     */
    public function api_blind_transfer(Request $request) {

    }

    /**
     * linkedid
     * pickup_linkedid
     * extension
     * start
     */
    public function api_pickup(Request $request) {

    }

    public function api_get_recording($id) {
        $call_recording = CallRecording::findOrFail($id);

        $json['url'] = '';
        if (!empty($call_recording->recordingfile)) {
            if (Storage::exists($call_recording->recordingfile)) {
                //RC: Si lo tenemos el en storage interno lo devolvemos
                $json['url'] = Storage::url('calls/' . $call_recording->recordingfile);
            } else {
                //RC: Si no lo tenemos en el store interno lo tenemos que ir a buscar a la centralita
                //RC: obtenemos la configuración de la centralita
                $api_host = env('PBX_HOST', '');
                $api_port = env('PBX_PORT', '');

                if($api_host != '' && $api_port != '') {
                    $call = Call::findOrFail($call_recording->call_id);
                    $recordingfile = '';

                    //RC: Seteamos las variables para identificar la llamada
                    $data['linkedid'] = $call->linkedid;
                    $data['uniqueid'] = $call->uniqueid;
                    $data['recordingfile'] = $call_recording->recordingfile;
                    
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $api_host.'/freePbxApi2/calls/get_record.php');
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

                        if(!empty($response->recordingfile)) {
                            $recordingfile = $response->recordingfile;
                        }
                    }

                    //RC: Si tenemos grabación la guardamos, sino guardamos un carácter vacio
                    if(!empty($recordingfile)) {
                        $url = $api_host.'/freePbxApi2/calls/temp_recordings/'.$recordingfile;

                        $arrContextOptions=array(
                            "ssl"=>array(
                                "verify_peer"=>false,
                                "verify_peer_name"=>false,
                            ),
                        );  
                        $contents = file_get_contents($url, false, stream_context_create($arrContextOptions));

                        if($contents) {
                            Storage::put('calls/'.$recordingfile, $contents);
                            $json['url'] = Storage::url('calls/' . $recordingfile);
                        }
                    }
                }
            }
        }

        return $json;
    }

    /**
     * Devuelve el número el identificador de una empresa en base al número de teléfono, si no existe devuelve un -1
     * @author Roger Corominas
     * @param String $number número de teléfono
     * @return Integer
     */
    private function get_company_id_by_number($number) {
        $phone_number = PhoneNumber::where('number', $number)
            ->first();

        if(!empty($phone_number)) {
            return $phone_number->company_id;
        } else {
            //RC: Si no tenemos número, miramos si tenemos una extensión
            $extension = Extension::where('number', $number)
                ->first();

            if(!empty($extension)) {
                return $extension->company_id;
            } else {
                return -1;
            }
        }
    }

    /**
     * @author Roger Corominas
     * Función que genera un evento identificado por $event_type de la llamada identificada por $current_call
     * @param CurrentCall $current_call llamada en curso
     * @param String $event_type tipo de evento que queremos generar
     * @param String $call_stat_event nombre del vento del objeto call_stat
     */
    private function generate_event_call_start($current_call, $event_type, $call_stat_event = '')
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
            broadcast(new EventsCallStart($call_stat, $current_call, $current_call->company_id));
        } else {
            broadcast(new CallUpdate($call_stat, $current_call, $current_call->company_id));
        }
    }

    /**
     * @author Roger Corominas
     * Función que genera un canal con la información del evento del AMI (personalizada con la hora del registro y la compañía)
     * @param Array $data Array con la información del evento + start y company_id
     * @param Integer $current_call_id identificar de la llamada asignada al canal
     */
    private function creat_pbx_channel_from_ami_event($data, $current_call_id)
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

    /**
     * @author Roger Corominas
     * Función que genera una llamada con la información del evento del AMI (personalizada con la hora del registro y la compañía)
     * @param Array data Array con la información del evento + start y company_id
     */
    private function create_call_from_ami_event($data)
    {
        //RC: Obtenemos los valores de quien llama y donde llama (pueden ser desconocidos)
        $from = $data['CallerIDNum'];
        $to = $data['Exten'];

        $trunk_name = self::get_channel_name($data['Channel']);

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
        if ($call_type_id == 1
        ) {
            $contact_phone = $from;
        } else if ($call_type_id == 2) {
            $contact_phone = $to;
        }

        if (!empty($contact_phone)) {
            $account_contact_type = AccountContactType::join('accounts', 'accounts.id', '=', 'account_contact_types.account_id')
            ->where('accounts.company_id', $data['company_id'])
                ->where(function ($query) use ($contact_phone) {
                    $query->orWhere('account_contact_types.value', $contact_phone)
                        ->orWhere('account_contact_types.value', str_replace('+', '00', $contact_phone))
                        ->orWhere('account_contact_types.value', substr($contact_phone, 1))
                        ->orWhere('account_contact_types.value', '+34' . $contact_phone)
                        ->orWhereRaw('REPLACE(account_contact_types.value, "+", "00") = "' . substr($contact_phone, 1) . '"')
                        ->orWhereRaw('REPLACE(account_contact_types.value, "+", "") = "' . $contact_phone . '"');
            })
            ->select('account_contact_types.*')
            ->first();
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

            //RC: Miramos si tenemos algun usuario con esta extensión
            $user = User::where('company_id', $current_call->company_id)
                ->where('extension', $extension)
                ->first();

            $current_call_user = new CurrentCallUser();
            $current_call_user->current_call_id = $current_call->id;
            if (!empty($user)) {
                $current_call_user->user_id = $user->id;
            }
            $current_call_user->extension = $extension;
            $current_call_user->start = $current_call->start;
            $current_call_user->save();
        }

        //RC: Guardamos un registro en el log
        $data_log['current_call_id'] = $current_call->id;
        $data_log['call_log_type_id'] = 1;
        $data_log['description'] = 'Inicio de la llamada';
        $data_log['start'] = $current_call->start;
        CurrentCallLog::create($data_log);

        return $current_call;
    }

    private function get_channel_name($channel_name)
    {
        //RC: Obtenemos el nombre del troncal
        $start = strpos($channel_name, '/') + 1;
        $length = strpos($channel_name, '-') - $start;
        return substr($channel_name, $start, $length);
    }

    private function get_channel_prefix($channel_name)
    {
        //RC: Obtenemos el nombre del troncal
        $start = 0;
        $length = strpos($channel_name, '/');
        return substr($channel_name, $start, $length);
    }

    private function get_extension_user($company_id, $extension)
    {
        return User::join('user_sessions', 'user_sessions.user_id', '=', 'users.id')
            ->where('user_sessions.extension', $extension)
            ->where('users.company_id', $company_id)
            ->orderBy('user_sessions.id', 'DESC')
            ->select('users.*')
            ->first();
    }

    public function forceClose()
    {
        $return = shell_exec('php artisan closecalls');

        dd($return);
    }
}
