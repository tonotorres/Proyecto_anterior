<?php

namespace App\Http\Controllers;

use App\CampaignFormInput;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CampaignFormInputsController extends Controller
{
    //
    /**
     * @var int clave del módulo
     */
    private $module_key = 55;
    private $relate_properties = [];

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del cuenta indicado
     */
    private $module;

    public function apiGetCampaignInput($campaign_id)
    {
        return CampaignFormInput::join('campaign_forms', 'campaign_forms.id', '=', 'campaign_form_inputs.campaign_form_id')
            ->join('campaign_campaign_form', 'campaign_campaign_form.campaign_form_id', '=', 'campaign_forms.id')
            ->where('campaign_campaign_form.campaign_id', '=', $campaign_id)
            ->distinct()
            ->select('campaign_form_inputs.name', 'campaign_form_inputs.label')
            ->get();
    }

    /**
     * @author Roger Corominas
     * Devuelve un array con todas las cuentas activos
     * @return CampaignFormInput[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all($campaign_form_id)
    {
        $this->module = get_user_module_security($this->module_key);
        $campaign_form_inputs = self::get_all($campaign_form_id);
        if (!empty($campaign_form_inputs)) {
            return $campaign_form_inputs;
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
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

        $campaign_form_input = self::get($id);
        if (!empty($campaign_form_input)) {
            return $campaign_form_input;
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta generamos una nueva cuenta, sino devolvemos los errores
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_store(Request $request, $campaign_form_id)
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->create)) {
            $data = self::validateData($request->all(), $campaign_form_id);
            $campaign_form_input = self::create($data, $campaign_form_id);

            if (empty($campaign_form_input['errors'])) {
                return $campaign_form_input->load($this->relate_properties);
            } else {
                return $campaign_form_input;
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
    public function api_update(Request $request, $campaign_form_id, int $id)
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->update)) {
            $data = self::validateData($request->all(), $campaign_form_id);
            $campaign_form_input = self::update($data, $campaign_form_id, $id);

            if (empty($campaign_form_input['errors'])) {
                return $campaign_form_input->load($this->relate_properties);
            } else {
                return $campaign_form_input;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_partial_update(Request $request, int $id)
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->update)) {
            $campaign_form_input = self::partial_update($request->all(), $id);

            if (empty($campaign_form_input['errors'])) {
                return $campaign_form_input->load($this->relate_properties);
            } else {
                return $campaign_form_input;
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
    public function api_delete(Request $request, int $campaign_form_id, int $id)
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->delete)) {
            return self::delete($id);
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    private function get_all($campaign_form_id)
    {
        //RC: Si no tenemos la seguridad del módulo lo volvemos a generar
        if (empty($this->module)) {
            $this->module = get_user_module_security($this->module_key);
        }

        $user = get_loged_user();
        if (!empty($this->module->read) || !empty($this->module->own)) {
            $campaign_form_input = CampaignFormInput::where('campaign_form_id', $campaign_form_id);

            return $campaign_form_input->get()->load($this->relate_properties);
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
            $campaign_form_input = CampaignFormInput::findOrFail($id);

            if ($campaign_form_input->campaign_form->company_id != $user->company_id) {
                return null;
            } else {
                //RC: Si es de la misma compañía lo podemos devolver, en caso contrario no lo 
                return $campaign_form_input->load($this->relate_properties);
            }
        } else {
            return null;
        }
    }

    private function validateData($data, $campaign_form_id)
    {
        if ($data['destination'] != 'aux') {
            $data['name'] = $data['destination'];
        } elseif (empty($data['name'])) {
            $data['name'] = Str::slug($data['label'], '-');
        }

        if (empty($data['position'])) {
            $data['position'] = CampaignFormInput::where('campaign_form_id', $campaign_form_id)->count() + 10;
        }

        return $data;
    }

    /**
     * @author Roger Corominas
     * Valida y genera un nuevo registro de la cuenta con los datos facilitados en $data
     * @param array $data Campos a introducir
     * @return array Devuelve el objeto generado o un array con los errores de validación
     */
    private function create(array $data, $campaign_form_id)
    {
        //RC: Obtenemos las validaciones
        $validations = get_user_template_fields_validations($this->module->user_template_id, $this->module->module_id);

        $data['campaign_form_id'] = $campaign_form_id;
        //$data = self::validate_default_values($data);

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.
            return ['errors' => $validator->errors()];
        } else {
            //RC: si la validación fue correcta tenemos que generar el objeto
            $user = get_loged_user();
            $data['company_id'] = $user->company_id;

            $campaign_form_input = CampaignFormInput::create($data);

            return $campaign_form_input;
        }
    }

    /**
     * @author Roger Corominas
     * Valida y actualiza el registro de la cuenta indentificado por $id con los datos del Array $data
     * @param array $data Campos a modificar
     * @param int $id Identificador de la cuenta
     * @return array Devuelve el objeto actualizado o un array con los errores de validación.
     */
    private function update(array $data, int $campaign_form_id, int $id)
    {
        //RC: Obtenemos las validaciones
        $validations = get_user_template_fields_validations($this->module->user_template_id, $this->module->module_id, $id);

        //$data = self::validate_default_values($data);

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.s
            return ['errors' => $validator->errors()];
        } else {
            //RC: si la validación fue correcta tenemos que generar el objeto
            $campaign_form_input = CampaignFormInput::findOrFail($id);
            $user = get_loged_user();
            if ($user->company_id == $campaign_form_input->campaign_form->company_id) {
                $campaign_form_input->update($data);
            }

            return $campaign_form_input;
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
            $campaign_form_input = CampaignFormInput::findOrFail($id);
            $user = get_loged_user();
            if ($user->company_id == $campaign_form_input->company_id) {
                $campaign_form_input->update($data);
            }

            return $campaign_form_input;
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
     * @return CampaignFormInput
     */
    private function delete(int $id)
    {
        $campaign_form_input = CampaignFormInput::findOrFail($id);
        $user = get_loged_user();
        if ($user->company_id == $campaign_form_input->campaign_form->company_id) {
            $campaign_form_input->delete();

            self::sortCampaignFormInputs($campaign_form_input->campaign_form_id);
        }

        return $campaign_form_input;
    }

    private function sortCampaignFormInputs($campaign_form_id)
    {
        $campaign_form_inputs = CampaignFormInput::where('campaign_form_id', $campaign_form_id)
            ->orderBy('position', 'ASC')
            ->get();

        $i = 10;
        foreach ($campaign_form_inputs as $campaign_form_input) {
            $campaign_form_input->position = $i;
            $campaign_form_input->save();

            $i = $i + 10;
        }
    }
}
