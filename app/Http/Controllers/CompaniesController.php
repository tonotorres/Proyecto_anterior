<?php

namespace App\Http\Controllers;

use App\Company;
use App\UserTemplateField;
use App\UserTemplateModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompaniesController extends Controller
{
    /**
     * @var int clave del módulo
     */
    private $module_key = 1;

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del usuario indicado
     */
    private $module;

    public function api_get_list() {
        $this->module = get_user_module_security($this->module_key);

        $user = get_loged_user();
        if(!empty($this->module->read)) {
            return $user->companies()->select('id', 'name as label')->get();
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Devuelve un array con todas las empresas activas
     * @param Request $request
     * @return Company[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all(Request $request) {
        $this->module = get_user_module_security($this->module_key);
        
        $user = get_loged_user();

        if(!empty($this->module->read)) {
            return $user->companies()->get()->load('company_configs', 'company_configs.company_config_group');
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Devuelve un objeto con la información de la empresa y las configuraciones
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function api_get(int $id) {
        $this->module = get_user_module_security($this->module_key);

        if(self::validate_security($id)) {
            if(!empty($this->module->read)) {
                return Company::findOrFail($id)->load('company_configs', 'company_configs.company_config_group');
            } else {
                return response()->json(['error' => 'unauthenticated'], 401);
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta generamos una empresa con la información facilitada
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_store(Request $request) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->create)) {
            $company = self::create($request->all());

            if(empty($company['errors'])) {
                return $company->load('company_configs', 'company_configs.company_config_group');
            } else {
                return $company;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta actualizamos la empresa identificado por el parámetro $id, con la información facilitada
     * @param Request $request
     * @param int $id Identificador de la empresa
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_update(Request $request, int $id) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->update)) {
            if(self::validate_security($id)) {
                $company = self::update($request->all(), $id)->load('company_configs', 'company_configs.company_config_group');

                if(empty($company['errors'])) {
                    return $company->load('company_configs', 'company_configs.company_config_group');
                } else {
                    return $company;
                }
            } else {
                return response()->json(['error' => 'unauthenticated'], 401);
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Eliminamos la empresa identificada por el campo Id
     * @param Request $request
     * @param int $id Identificador de la empresa
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function api_delete(Request $request, int $id) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->delete)) {
            if(self::validate_security($id)) {
                return self::delete($id);
            } else {
                return response()->json(['error' => 'unauthenticated'], 401);
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Valida y genera un nuevo registro de empresa con los datos facilitados en $data
     * @param array $data Campos a introducir
     * @return array Devuelve el objeto generado o un array con los errores de validación
     */
    private function create (Array $data) {
        //RC: Obtenemos las validaciones
        $validations = get_user_template_fields_validations($this->module->user_template_id, $this->module->module_id);

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.
            return ['errors' => $validator->errors()];
        } else {
            //RC: si la validación fue correcta tenemos que generar el objeto
            $company = Company::create($data);

            $user = get_loged_user();
            $user->companies()->attach($company->id);

            return $company;
        }
    }

    /**
     * @author Roger Corominas
     * Valida y actualiza el registro de la empresa indentificado por $id con los datos del Array $data
     * @param array $data Campos a modificar
     * @param int $id Identificador de la empresa
     * @return array Devuelve el objeto actualizado o un array con los errores de validación.
     */
    private function update (Array $data, int $id) {
        //RC: Obtenemos las validaciones
        $validations = get_user_template_fields_validations($this->module->user_template_id, $this->module->module_id, $id);

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.
            return ['errors' => $validator->errors()];
        } else {
            //RC: si la validación fue correcta tenemos que generar el objeto
            $company = Company::findOrFail($id);
            $company->update($data);

            return $company;
        }
    }

    /**
     * @author Roger Corominas
     * Elimina el objeto
     * @param int $id Identificador de la empresa
     * @return Company
     */
    private function delete (int $id) {
        $company = Company::findOrFail($id);
        $company->delete();
        return $company;
    }

    

    /**
     * @param integer $id Identificador de la empresa
     * Devuelve si tenemos permiso para acceder a este elemento
     * @return bool 
     */
    private function validate_security(int $id) {
        $loged_user = get_loged_user();
        
        if($loged_user->companies()->where('id', $id)->count() > 0) {
            return true;
        } else {
            return false;
        }
    }
}
