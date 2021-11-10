<?php

namespace App\Http\Controllers;

use App\Call;
use App\Campaign;
use App\CampaignAnswer;
use App\CampaignContact;
use App\CampaignInCall;
use App\CampaignOutCall;
use App\CampaignUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignsController extends Controller
{
    /**
     * @var int clave del módulo
     */
    private $module_key = 54;
    private $relate_properties = ['campaign_in_call', 'campaign_out_call', 'campaign_forms', 'campaign_answer_ends', 'users', 'campaign_urls'];

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del cuenta indicado
     */
    private $module;

    public function apiGetGeneralReport($id)
    {
        $json['total_contacts'] = CampaignContact::where('campaign_id', $id)->count();
        $json['total_campaign_answers'] = CampaignAnswer::where('campaign_id', $id)->count();
        $json['total_calls'] = Call::where('campaign_id', $id)->count();
        $json['total_whatsapps'] = 0;

        $json['contacts_history'] = DB::table('campaign_contacts')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as views'))
            ->where('campaign_id', $id)
            ->groupBy('date')
            ->get();

        $json['campaign_answers_history'] = DB::table('campaign_answers')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as views'))
            ->where('campaign_id', $id)
            ->groupBy('date')
            ->get();

        $json['calls_history'] = DB::table('calls')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as views'))
            ->where('campaign_id', $id)
            ->groupBy('date')
            ->get();

        $wait[0]['label'] = '< 30s';
        $wait[0]['total'] = Call::where('campaign_id', $id)
        ->where('call_type_id', 1)
        ->where('duration_wait', '<=', '30')
        ->count();

        $wait[1]['label'] = '< 60s';
        $wait[1]['total'] = Call::where('campaign_id', $id)
        ->where('call_type_id', 1)
        ->where('duration_wait', '>', '30')
            ->where('duration_wait', '<=', '60')
            ->count();

        $wait[2]['label'] = '< 60s';
        $wait[2]['total'] = Call::where('campaign_id', $id)
        ->where('call_type_id', 1)
        ->where('duration_wait', '>', '60')
            ->count();

        $json['calls_wait'] = $wait;


        $duration = [];
        for ($i = 0; $i < 10; $i++) {
            $duration[$i]['label'] = '< ' . ($i + 1) . 'm';
            $duration[$i]['total'] = Call::where('campaign_id', $id)
            ->where('duration', '>', $i * 60)
                ->where('duration', '<=', ($i + 1) * 60)
                ->count();
        }

        $duration[10]['label'] = '> 10m';
        $duration[10]['total'] = Call::where('campaign_id', $id)
        ->where('duration', '>', 600)
        ->count();

        $json['calls_duration'] = $duration;

        $administrative_time[0]['label'] = '< 30s';
        $administrative_time[0]['total'] = Call::where('campaign_id', $id)
        ->where('administrative_time', '<=', '30')
        ->count();

        $administrative_time[1]['label'] = '< 60s';
        $administrative_time[1]['total'] = Call::where('campaign_id', $id)
        ->where('administrative_time', '>', '30')
            ->where('administrative_time', '<=', '60')
            ->count();

        $administrative_time[2]['label'] = '< 60s';
        $administrative_time[2]['total'] = Call::where('campaign_id', $id)
        ->where('administrative_time', '>', '60')
            ->count();

        $json['calls_administrative_time'] = $administrative_time;

        return $json;
    }
    /**
     * @author Roger Corominas
     * Devuelve un array con todas las cuentas activos
     * @return Campaign[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all()
    {
        $this->module = get_user_module_security($this->module_key);
        $campaigns = self::get_all();
        if (!empty($campaigns)) {
            return $campaigns;
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_get_list()
    {

        return Campaign::select('id', 'name as label')
            ->orderBy('label', 'ASC')
            ->get();
    }

    /**
     * @author Roger Corominas
     * Devuelve un objeto con la información de la cuenta
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function api_get(int $id)
    {
        $this->module = get_user_module_security($this->module_key);

        $campaign = self::get($id);
        if (!empty($campaign)) {
            return $campaign;
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }
    public function api_get_by_token($token)
    {
        $campaing_url = CampaignUrl::where('token', $token)->first();

        if(empty($campaing_url)) {
            abort(404);
        }

        $campaign = $campaing_url->campaign;

        return $campaign->load($this->relate_properties);
    }

    public function api_get_campaign_answer_ends($id)
    {
        $campaign = Campaign::findOrFail($id);

        return $campaign->campaign_answer_ends;
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta generamos una nueva cuenta, sino devolvemos los errores
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_store(Request $request)
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->create)) {
            $campaign = self::create($request->all());

            if (empty($campaign['errors'])) {
                return $campaign->load($this->relate_properties);
            } else {
                return $campaign;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta actualizamos la cuenta identificada por el parámetro $id, con la información facilitada
     * @param Request $request
     * @param int $id Identificador de la cuenta
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_update(Request $request, int $id)
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->update)) {
            $campaign = self::update($request->all(), $id);

            if (empty($campaign['errors'])) {
                return $campaign->load($this->relate_properties);
            } else {
                return $campaign;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_partial_update(Request $request, int $id)
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->update)) {
            $campaign = self::partial_update($request->all(), $id);

            if (empty($campaign['errors'])) {
                return $campaign->load($this->relate_properties);
            } else {
                return $campaign;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Eliminamos la cuenta identificada por el campo Id
     * @param Request $request
     * @param int $id Identificador de la cuenta
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function api_delete(Request $request, int $id)
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->delete)) {
            return self::delete($id);
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    private function get_all()
    {
        //RC: Si no tenemos la seguridad del módulo lo volvemos a generar
        if (empty($this->module)) {
            $this->module = get_user_module_security($this->module_key);
        }

        $user = get_loged_user();
        if (!empty($this->module->read)) {
            $campaign = Campaign::where('company_id', $user->company_id);

            return $campaign->get()->load($this->relate_properties);
        } else if (!empty($this->module->own)) {
            $campaign = Campaign::where('company_id', $user->company_id);

            return $campaign->get()->load($this->relate_properties);
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    private function get($id)
    {
        //RC: Si no tenemos la seguridad del módulo lo volvemos a generar
        if (empty($this->module)) {
            $this->module = get_user_module_security($this->module_key);
        }

        //RC: Miramos si tenemos permisos para leer el objecto
        if (!empty($this->module->read) || !empty($this->module->own)) {
            $user = get_loged_user();
            $campaign = Campaign::findOrFail($id);

            if ($campaign->company_id != $user->company_id) {
                return null;
            } else {
                if (!empty($this->module->read)) {
                    //RC: Si es de la misma compañía lo podemos devolver, en caso contrario no lo 
                    return $campaign->load($this->relate_properties);
                } else {
                    if ($campaign->users()->where('id', $user->id)->count() > 0) {
                        return $campaign->load($this->relate_properties);
                    } else {
                        return null;
                    }
                }
            }
        } else {
            return null;
        }

        if (!empty($this->module->read)) {
            return Campaign::findOrFail($id);
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Valida y genera un nuevo registro de la cuenta con los datos facilitados en $data
     * @param array $data Campos a introducir
     * @return array Devuelve el objeto generado o un array con los errores de validación
     */
    private function create(array $data)
    {
        //RC: Obtenemos las validaciones
        $validations = get_user_template_fields_validations($this->module->user_template_id, $this->module->module_id);

        $data = self::validate_default_values($data);

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.
            return ['errors' => $validator->errors()];
        } else {
            //RC: si la validación fue correcta tenemos que generar el objeto
            $user = get_loged_user();
            $data['company_id'] = $user->company_id;

            $campaign = Campaign::create($data);

            if (empty($data['users_id'])) {
                $data['users_id'][] = $user->id;
            }

            if (!empty($data['campaignInCallStart'])) {
                $dataCampaignInCall = new CampaignInCall();
                $dataCampaignInCall->start = $data['campaignInCallStart'];
                $dataCampaignInCall->end = $data['campaignInCallEnd'];
                $dataCampaignInCall->queue = $data['campaignInCallQueue'];
                $dataCampaignInCall->administrative_time = $data['campaignInCallAdministrativeTime'];
                $campaign->campaign_in_call()->save($dataCampaignInCall);
            }

            if (!empty($data['campaignOutCallStart'])) {
                $dataCampaignOutCall = new CampaignOutCall();
                $dataCampaignOutCall->start = $data['campaignOutCallStart'];
                $dataCampaignOutCall->end = $data['campaignOutCallEnd'];
                $dataCampaignOutCall->start_time = $data['campaignOutCallStartTime'];
                $dataCampaignOutCall->end_time = $data['campaignOutCallEndTime'];
                $dataCampaignOutCall->route_out_id = $data['campaignOutCallRouteOutId'];
                $dataCampaignOutCall->administrative_time = $data['campaignOutCallAdministrativeTime'];
                $campaign->campaign_out_call()->save($dataCampaignOutCall);
            }

            $campaign->campaign_answer_ends()->sync($data['campaign_answer_ends_id']);
            $campaign->campaign_forms()->sync($data['campaign_forms_id']);
            $campaign->users()->sync($data['users_id']);

            return $campaign;
        }
    }

    /**
     * @author Roger Corominas
     * Valida y actualiza el registro de la cuenta indentificado por $id con los datos del Array $data
     * @param array $data Campos a modificar
     * @param int $id Identificador de la cuenta
     * @return array Devuelve el objeto actualizado o un array con los errores de validación.
     */
    private function update(array $data, int $id)
    {
        //RC: Obtenemos las validaciones
        $validations = get_user_template_fields_validations($this->module->user_template_id, $this->module->module_id, $id);

        $data = self::validate_default_values($data);

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.s
            return ['errors' => $validator->errors()];
        } else {
            //RC: si la validación fue correcta tenemos que generar el objeto
            $campaign = Campaign::findOrFail($id);
            $user = get_loged_user();
            if ($user->company_id == $campaign->company_id) {
                $campaign->update($data);

                if (empty($data['users_id'])) {
                    $data['users_id'][] = $user->id;
                }

                if ($campaign->campaign_in_call()->count() > 0) {
                    $dataCampaignInCall = $campaign->campaign_in_call;
                } else {
                    $dataCampaignInCall = new CampaignInCall();
                }
                if (!empty($data['campaignInCallStart'])) {
                    $dataCampaignInCall->start = $data['campaignInCallStart'];
                    $dataCampaignInCall->end = $data['campaignInCallEnd'];
                    $dataCampaignInCall->queue = $data['campaignInCallQueue'];
                    $dataCampaignInCall->administrative_time = $data['campaignInCallAdministrativeTime'];
                    $campaign->campaign_in_call()->save($dataCampaignInCall);
                }

                if ($campaign->campaign_out_call()->count() > 0) {
                    $dataCampaignOutCall = $campaign->campaign_out_call;
                } else {
                    $dataCampaignOutCall = new CampaignOutCall();
                }
                if (!empty($data['campaignOutCallStart'])) {
                    $dataCampaignOutCall->start = $data['campaignOutCallStart'];
                    $dataCampaignOutCall->end = $data['campaignOutCallEnd'];
                    $dataCampaignOutCall->start_time = $data['campaignOutCallStartTime'];
                    $dataCampaignOutCall->end_time = $data['campaignOutCallEndTime'];
                    $dataCampaignOutCall->route_out_id = $data['campaignOutCallRouteOutId'];
                    $dataCampaignOutCall->administrative_time = $data['campaignOutCallAdministrativeTime'];
                    $campaign->campaign_out_call()->save($dataCampaignOutCall);
                }

                $campaign->campaign_answer_ends()->sync($data['campaign_answer_ends_id']);
                $campaign->campaign_forms()->sync($data['campaign_forms_id']);
                $campaign->users()->sync($data['users_id']);
            }

            return $campaign;
        }
    }

    private function partial_update(array $data, int $id)
    {
        //RC: Obtenemos las validaciones
        $validations = get_user_template_fields_partial_validations($this->module->user_template_id, $this->module->module_id, $data, $id);

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.
            return ['errors' => $validator->errors()];
        } else {
            //RC: si la validación fue correcta tenemos que generar el objeto
            $campaign = Campaign::findOrFail($id);
            $user = get_loged_user();
            if ($user->company_id == $campaign->company_id) {
                $campaign->update($data);

                if (!empty($data['users_id'])) {
                    $campaign->users()->sync($data['users_id']);
                }
                if (!empty($data['campaign_forms_id'])) {
                    $campaign->campaign_forms()->sync($data['campaign_forms_id']);
                }
                if (!empty($data['campaign_answer_ends_id'])) {
                    $campaign->campaign_answer_ends()->sync($data['campaign_answer_ends_id']);
                }
            }

            return $campaign;
        }
    }

    private function validate_default_values($data)
    {
        $user = get_loged_user();

        return $data;
    }

    /**
     * @author Roger Corominas
     * Elimina el objeto
     * @param int $id Identificador de la cuenta
     * @return Campaign
     */
    private function delete(int $id)
    {
        $campaign = Campaign::findOrFail($id);
        $user = get_loged_user();
        if ($user->company_id == $campaign->company_id) {
            $campaign->delete();
        }

        return $campaign;
    }
}
