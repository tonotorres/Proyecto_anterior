<?php

namespace App\Http\Controllers;

use App\Campaign;
use App\CampaignCall;
use App\CampaignOutCall;
use Illuminate\Http\Request;

class CampaignCallsController extends Controller
{
    private $module_key = 59;
    private $related_properties = [];

    private $module;

    public function markCallAsFailed(Request $request)
    {
        $campaign_id = $request->call['campaign_id'];
        $phone = substr($request->call['to'], 1);

        $campaignCall = CampaignCall::where('campaign_id', $campaign_id)
            ->where('phone', $phone)
            ->first();

        if (!empty($campaignCall)) {
            $campaignCall->is_correct = false;
            $campaignCall->save();
        }

        return $campaignCall;
    }

    private function getNextCampaignCall($campaign_id)
    {
        $user = get_loged_user();

        $campaign_call = CampaignCall::where('campaign_id', $campaign_id)
            ->where('is_correct', 0)
            ->where('is_paused', 0)
            ->where('is_blocked', 0)
            ->whereRaw('retries < total_retries')
            ->whereNull('ringing_user_id')
            ->orderBy('weight', 'DESC')
            ->orderBy('retries', 'ASC')
            ->orderBy('id', 'ASC')
            ->first();

        if (!empty($campaign_call)) {
            $campaign_call->ringing_user_id = $user->id;
            $campaign_call->retries++;
            $campaign_call->save();

            //RC: Mirmaos si tenemos que añadir algun prefijo
            $campaign_out_call = CampaignOutCall::where('campaign_id', $campaign_id)->first();

            if (!empty($campaign_out_call)) {
                if (!empty($campaign_out_call->route_out->prefix)) {
                    $campaign_call->phone = $campaign_out_call->route_out->prefix . $campaign_call->phone;
                }
            }
        }

        return $campaign_call;
    }

    public function api_get_next_campaign_call($campaign_id)
    {
        $campaign = Campaign::findOrFail($campaign_id);

        if ($campaign->active == 1) {
            return self::getNextCampaignCall($campaign_id);
        } else {
            return [];
        }
    }

    public function api_search(Request $request, $page = 0)
    {
        $user = get_loged_user();

        $campaign_calls = CampaignCall::select('campaign_calls.*');

        if (!empty($request->campaign_id)) {
            $campaign_calls->where('campaign_calls.campaign_id', $request->campaign_id);
        }

        if (!empty($request->name)) {
            $campaign_calls->where('campaign_calls.name', 'like', '%' . $request->name . '%');
        }

        if (!empty($request->phone)) {
            $campaign_calls->where('campaign_calls.phone', 'like', '%' . $request->phone . '%');
        }

        if (!empty($request->weight)) {
            $campaign_calls->where('campaign_calls.weight', $request->weight);
        }

        if (isset($request->is_paused) && $request->is_paused != '') {
            $campaign_calls->where('campaign_calls.is_paused', $request->is_paused);
        }

        if (isset($request->is_blocked) && $request->is_blocked != '') {
            $campaign_calls->where('campaign_calls.is_blocked', $request->is_blocked);
        }

        if (isset($request->is_correct) && $request->is_correct != '') {
            $campaign_calls->where('campaign_calls.is_correct', $request->is_correct);
        }

        if (!empty($request->total_retries)) {
            $campaign_calls->where('campaign_calls.total_retries', $request->total_retries);
        }

        if (!empty($request->retries)) {
            $campaign_calls->where('campaign_calls.retries', $request->retries);
        }


        $limit = $request->limit;
        $limit_start = ($page - 1) * $limit;

        if (!empty($request->sortColumn)) {
            $sortColumn = $request->sortColumn;
        } else {
            $sortColumn = 'name';
        }
        if ($request->sortDirection == -1) {
            $sortDirection = 'desc';
        } else {
            $sortDirection = 'asc';
        }

        $json['page'] = (int)$page;
        $json['limit'] = $limit;
        $json['limit_start'] = $limit_start;
        $json['total'] = $campaign_calls->count('campaign_calls.id');
        $json['total_pages'] = ceil($json['total'] / $limit);
        $json['data'] = $campaign_calls
            ->orderBy($sortColumn, $sortDirection)
            ->limit($limit)
            ->offset($limit_start)
            ->get()
            ->load($this->related_properties);

        return $json;
    }

    /**
     * @author Roger Corominas
     * Devuelve un array con todas las cuentas activos
     * @return CampaignCall[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all($campaign_call_list_id = null)
    {
        $this->module = get_user_module_security($this->module_key);
        $campaign_calls = self::get_all();
        if (!empty($campaign_calls)) {
            return $campaign_calls;
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_get_list()
    {

        return CampaignCall::select('id', 'name as label')
            ->orderBy('label', 'ASC')
            ->get();
    }

    /**
     * @author Roger Corominas
     * Devuelve un objeto con la incontactación de la cuenta
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function api_get(int $id)
    {
        $this->module = get_user_module_security($this->module_key);

        $campaign_call = self::get($id);
        if (!empty($campaign_call)) {
            return $campaign_call;
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la incontactación es correcta generamos una nueva cuenta, sino devolvemos los errores
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_store(Request $request)
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->create)) {
            $campaign_call = self::create($request->all());

            if (empty($campaign_call['errors'])) {
                return $campaign_call->load($this->related_properties);
            } else {
                return $campaign_call;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la incontactación es correcta actualizamos la cuenta identificada por el parámetro $id, con la incontactación facilitada
     * @param Request $request
     * @param int $id Identificador de la cuenta
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_update(Request $request, int $id)
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->update)) {
            $campaign_call = self::update($request->all(), $id);

            if (empty($campaign_call['errors'])) {
                return $campaign_call->load($this->related_properties);
            } else {
                return $campaign_call;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_partial_update(Request $request, int $id)
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->update)) {
            $campaign_call = self::partial_update($request->all(), $id);

            if (empty($campaign_call['errors'])) {
                return $campaign_call->load($this->related_properties);
            } else {
                return $campaign_call;
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

    public function api_import(Request $request, int $campaignId)
    {
        $this->module = get_user_module_security($this->module_key);
        if (!empty($this->module->create)) {
            if (!empty($request->str_calls)) {
                $campaignCalls = explode("\n", $request->str_calls);

                $isHeader = true;
                foreach ($campaignCalls as $cC) {
                    if (!$isHeader) {
                        unset($data);

                        $campaignCall = explode("\t", $cC);
                        if (count($campaignCall) <= 4) {
                            $data = self::getDataFromImportRow($campaignCall);
                            $data['campaign_id'] = $campaignId;

                            $return = self::create($data);
                        }
                    } else {
                        $isHeader = false;
                    }
                }
                return ['error' => 0];
            } else {
                return ['error' => 1];
            }
        } else {
            return ['error' => 2];
        }
    }

    private function getDataFromImportRow($row)
    {
        $data = [];
        if (!empty($row[0])) {
            $data['phone'] = $row[0];
        }
        if (!empty($row[1])) {
            $data['name'] = $row[1];
        }
        if (!empty($row[2])) {
            $data['weight'] = $row[2];
        } else {
            $data['weight'] = 1;
        }

        if (!empty($row[3])) {
            $data['total_retries'] = $row[3];
        } else {
            $data['total_retries'] = 1;
        }

        return $data;
    }

    private function get_all()
    {
        //RC: Si no tenemos la seguridad del módulo lo volvemos a generar
        if (empty($this->module)) {
            $this->module = get_user_module_security($this->module_key);
        }

        $user = get_loged_user();
        if (!empty($this->module->read)) {
            $campaign_call = CampaignCall::where('company_id', $user->company_id);

            return $campaign_call->get()->load($this->related_properties);
        } else if (!empty($this->module->own)) {
            $campaign_call = CampaignCall::where('company_id', $user->company_id);

            return $campaign_call->get()->load($this->related_properties);
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
            $campaign_call = CampaignCall::findOrFail($id);

            if ($campaign_call->company_id != $user->company_id) {
                return null;
            } else {
                //RC: Si es de la misma compañía lo podemos devolver, en caso contrario no lo 
                return $campaign_call->load($this->related_properties);
            }
        } else {
            return null;
        }

        if (!empty($this->module->read)) {
            return CampaignCall::findOrFail($id);
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

            $campaign_call = CampaignCall::create($data);

            return $campaign_call;
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
            $campaign_call = CampaignCall::findOrFail($id);
            $user = get_loged_user();
            if ($user->company_id == $campaign_call->campaign->company_id) {
                $campaign_call->update($data);
            }

            return $campaign_call;
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
            $campaign_call = CampaignCall::findOrFail($id);
            $user = get_loged_user();
            if ($user->company_id == $campaign_call->company_id) {
                $campaign_call->update($data);
            }

            return $campaign_call;
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
     * @return CampaignCall
     */
    private function delete(int $id)
    {
        $campaign_call = CampaignCall::findOrFail($id);
        $user = get_loged_user();
        if ($user->company_id == $campaign_call->company_id) {
            $campaign_call->delete();
        }

        return $campaign_call;
    }
}
