<?php

namespace App\Http\Controllers;

use App\CampaignForm;
use Illuminate\Http\Request;

class CampaignFormsController extends Controller
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

    /**
     * @author Roger Corominas
     * Devuelve un array con todas las cuentas activos
     * @return CampaignForm[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all($campaign_form_list_id = null)
    {
        $this->module = get_user_module_security($this->module_key);
        $campaign_forms = self::get_all();
        if (!empty($campaign_forms)) {
            return $campaign_forms;
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_get_list()
    {
        $user = get_loged_user();

        return CampaignForm::where('company_id', $user->company_id)
            ->select('id', 'name as label')
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

        $campaign_form = self::get($id);
        if (!empty($campaign_form)) {
            return $campaign_form;
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
    public function api_store(Request $request)
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->create)) {
            $campaign_form = self::create($request->all());

            if (empty($campaign_form['errors'])) {
                return $campaign_form->load($this->relate_properties);
            } else {
                return $campaign_form;
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
            $campaign_form = self::update($request->all(), $id);

            if (empty($campaign_form['errors'])) {
                return $campaign_form->load($this->relate_properties);
            } else {
                return $campaign_form;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_partial_update(Request $request, int $id)
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->update)) {
            $campaign_form = self::partial_update($request->all(), $id);

            if (empty($campaign_form['errors'])) {
                return $campaign_form->load($this->relate_properties);
            } else {
                return $campaign_form;
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
            $campaign_form = CampaignForm::where('company_id', $user->company_id);

            return $campaign_form->get()->load($this->relate_properties);
        } else if (!empty($this->module->own)) {
            $campaign_form = CampaignForm::where('company_id', $user->company_id);

            return $campaign_form->get()->load($this->relate_properties);
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
            $campaign_form = CampaignForm::findOrFail($id);

            if ($campaign_form->company_id != $user->company_id) {
                return null;
            } else {
                //RC: Si es de la misma compañía lo podemos devolver, en caso contrario no lo 
                return $campaign_form->load($this->relate_properties);
            }
        } else {
            return null;
        }

        if (!empty($this->module->read)) {
            return CampaignForm::findOrFail($id);
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

            $campaign_form = CampaignForm::create($data);

            return $campaign_form;
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
            $campaign_form = CampaignForm::findOrFail($id);
            $user = get_loged_user();
            if ($user->company_id == $campaign_form->company_id) {
                $campaign_form->update($data);
            }

            return $campaign_form;
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
            $campaign_form = CampaignForm::findOrFail($id);
            $user = get_loged_user();
            if ($user->company_id == $campaign_form->company_id) {
                $campaign_form->update($data);
            }

            return $campaign_form;
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
     * @return CampaignForm
     */
    private function delete(int $id)
    {
        $campaign_form = CampaignForm::findOrFail($id);
        $user = get_loged_user();
        if ($user->company_id == $campaign_form->company_id) {
            $campaign_form->delete();
        }

        return $campaign_form;
    }
}
