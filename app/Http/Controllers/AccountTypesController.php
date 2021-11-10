<?php

namespace App\Http\Controllers;

use App\AccountType;
use App\UserTemplateModule;
use Illuminate\Http\Request;

class AccountTypesController extends Controller
{
    /**
     * @var int clave del módulo
     */
    private $module_key = 5;

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del cuenta indicado
     */
    private $module;

    /**
     * @author Roger Corominas
     * Devuelve un listado con el id y el nombre de los tipos de cuentas.
     * @return Array|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_list() {
        $this->module = get_user_module_security($this->module_key);

        $user = get_loged_user();
        if(!empty($this->module->read)) {
            return AccountType::whereNull('company_id')
                ->orWhere('company_id', $user->company_id)
                ->select('id', 'name as label')
                ->get();
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Devuelve un array con todos los tipos de cuenta activos
     * @param Request $request
     * @return AccountType[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all(Request $request) {
        $this->module = get_user_module_security($this->module_key);
        $user = get_loged_user();

        if(!empty($this->module->read)) {
            return AccountType::whereNull('company_id')
            ->orWhere('company_id', $user->company_id)
            ->get();
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Devuelve un objeto con la información del tipo de cuenta
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function api_get(int $id) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->read)) {
            if(self::validate_security($id)) {
                return AccountType::findOrFail($id);
            } else {
                return response()->json(['error' => 'unauthenticated'], 401);
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta generamos un nuevo tipo de cuenta, sino devolvemos los errores
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_store(Request $request) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->create)) {
            $account_type = self::create($request->all());

            if(empty($account_type['errors'])) {
                return $account_type;
            } else {
                return $account_type;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta actualizamos el tipo de cuenta identificado por el parámetro $id, con la información facilitada
     * @param Request $request
     * @param int $id Identificador del tipo de cuenta
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_update(Request $request, int $id) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->update)) {
            if(self::validate_security($id)) {
                $account_type = self::update($request->all(), $id);

                if(empty($account_type['errors'])) {
                    return $account_type;
                } else {
                    return $account_type;
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
     * Eliminamos el tipo de cuenta identificada por el campo Id
     * @param Request $request
     * @param int $id Identificador del tipo de cuenta
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
     * Valida y genera un nuevo registro del tipo de cuenta con los datos facilitados en $data
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
            $user = get_loged_user();
            $data['company_id'] = $user->company_id;
            //RC: si la validación fue correcta tenemos que generar el objeto
            return AccountType::create($data);
        }
    }

    /**
     * @author Roger Corominas
     * Valida y actualiza el registro del tipo de cuenta indentificado por $id con los datos del Array $data
     * @param array $data Campos a modificar
     * @param int $id Identificador del tipo de cuenta
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
            $account_type = AccountType::findOrFail($id);
            $account_type->update($data);

            return $account_type;
        }
    }

    /**
     * @author Roger Corominas
     * Elimina el objeto
     * @param int $id Identificador del tipo de empresa
     * @return AccountType
     */
    private function delete (int $id) {
        $account_type = AccountType::findOrFail($id);
        $account_type->delete();
        return $account_type;
    }

    /**
     * @param integer $id Identificador del tipo de usuario
     * Devuelve si tenemos permiso para acceder a este elemento
     * @return bool 
     */
    private function validate_security(int $id) {
        $loged_user = get_loged_user();
        $account_type = AccountType::findOrFail($id);
        if(empty($account_type->company_id) || $account_type->company_id == $loged_user->company_id) {
            return true;
        } else {
            return false;
        }
    }
}
