<?php

namespace App\Http\Controllers;

use App\AccountAddress;
use Illuminate\Http\Request;

class AccountAddressesController extends Controller
{
    /**
     * @var int clave del módulo
     */
    private $module_key = 21;

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del cuenta indicado
     */
    private $module;

    /**
     * @author Roger Corominas
     * Devuelve un array con todos los account_addressos activos
     * @param Request $request
     * @return AccountAddress[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all(Request $request) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->read)) {
            return AccountAddress::all();
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Devuelve un objeto con la información del account_addresso
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function api_get(int $id) {
        $this->module = get_user_module_security($this->module_key);
        $user = get_loged_user();

        if(!empty($this->module->read)) {
            $account_address = AccountAddress::findOrFail($id);
            if($account_address->account->company_id == $user->company_id) {
                return $account_address;
            } else {
                return response()->json(['error' => 'unauthenticated'], 401);    
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta generamos un nuevo account_addresso, sino devolvemos los errores
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_store(Request $request) {
        $this->module = get_user_module_security($this->module_key);
        if(!empty($this->module->create)) {
            $account_address = self::create($request->all());

            if(empty($account_address['errors'])) {
                return $account_address;
            } else {
                return $account_address;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta actualizamos el account_addresso identificado por el parámetro $id, con la información facilitada
     * @param Request $request
     * @param int $id Identificador del tipo de cuenta
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_update(Request $request, int $id) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->update)) {
            $account_address = self::update($request->all(), $id);

            if(empty($account_address['errors'])) {
                return $account_address;
            } else {
                return $account_address;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Eliminamos el account_addresso identificada por el campo Id
     * @param Request $request
     * @param int $id Identificador del tipo de cuenta
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function api_delete(Request $request, int $id) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->delete)) {
            return self::delete($id);
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Valida y genera un nuevo registro del account_addresso con los datos facilitados en $data
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
            $account_address = AccountAddress::create($data);

            return $account_address;
        }
    }

    /**
     * @author Roger Corominas
     * Valida y actualiza el registro del account_addresso indentificado por $id con los datos del Array $data
     * @param array $data Campos a modificar
     * @param int $id Identificador del account_addresso
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
            $account_address = AccountAddress::findOrFail($id);
            $user = get_loged_user();

            if($account_address->account->company_id == $user->company_id) {
                $account_address->update($data);
                return $account_address;
            } else {
                return ['errors' => true];
            }
        }
    }

    /**
     * @author Roger Corominas
     * Elimina el objeto
     * @param int $id Identificador del account_addresso
     * @return AccountAddress
     */
    private function delete (int $id) {
        $account_address = AccountAddress::findOrFail($id);
        $account_address->delete();

        return $account_address;
    }
}
