<?php

namespace App\Http\Controllers;

use App\UserTemplateModule;
use App\UserType;
use Illuminate\Http\Request;

class UserTypesController extends Controller
{
    /**
     * @var int clave del módulo
     */
    private $module_key = 3;

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del usuario indicado
     */
    private $module;

    /**
     * @author Roger Corominas
     * Devuelve un listado con el id y el nombre de los tipos de usuarios.
     * @return Array|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_list() {
        $this->module = get_user_module_security($this->module_key);

        $user = get_loged_user();
        if(!empty($this->module->read)) {
            return UserType::whereNull('company_id')
                ->orWhere('company_id', $user->company_id)
                ->select('id', 'name as label')
                ->get();
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Devuelve un array con todos los tipos de usuario activos
     * @param Request $request
     * @return UserType[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all(Request $request) {
        $this->module = get_user_module_security($this->module_key);
        $user = get_loged_user();

        if(!empty($this->module->read)) {
            return UserType::whereNull('company_id')
                ->orWhere('company_id', $user->company_id)
                ->get();
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Devuelve un objeto con la información del tipo de usuario
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function api_get(int $id) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->read)) {
            if(self::validate_security($id)) {
                return UserType::findOrFail($id);
            } else {
                return response()->json(['error' => 'unauthenticated'], 401);    
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta generamos un nuevo tipo de usuario, sino devolvemos los errores
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_store(Request $request) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->create)) {
            $user_type = self::create($request->all());

            if(empty($user_type['errors'])) {
                return $user_type;
            } else {
                return $user_type;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta actualizamos el tipo de usuario identificado por el parámetro $id, con la información facilitada
     * @param Request $request
     * @param int $id Identificador del tipo de usuario
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_update(Request $request, int $id) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->update)) {
            if(self::validate_security($id)) {
                $user_type = self::update($request->all(), $id);

                if(empty($user_type['errors'])) {
                    return $user_type;
                } else {
                    return $user_type;
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
     * Eliminamos el tipo de usuario identificada por el campo Id
     * @param Request $request
     * @param int $id Identificador del tipo de usuario
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
     * Valida y genera un nuevo registro del tipo de usuario con los datos facilitados en $data
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
            return UserType::create($data);
        }
    }

    /**
     * @author Roger Corominas
     * Valida y actualiza el registro del tipo de usuario indentificado por $id con los datos del Array $data
     * @param array $data Campos a modificar
     * @param int $id Identificador del tipo de usuario
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
            $user_type = UserType::findOrFail($id);
            $user_type->update($data);

            return $user_type;
        }
    }

    /**
     * @author Roger Corominas
     * Elimina el objeto
     * @param int $id Identificador del tipo de empresa
     * @return UserType
     */
    private function delete (int $id) {
        $user_type = UserType::findOrFail($id);
        $user_type->delete();
        return $user_type;
    }

    /**
     * @param integer $id Identificador del tipo de usuario
     * Devuelve si tenemos permiso para acceder a este elemento
     * @return bool 
     */
    private function validate_security(int $id) {
        $loged_user = get_loged_user();
        $user_type = UserType::findOrFail($id);
        if(empty($user_type->company_id) || $user_type->company_id == $loged_user->company_id) {
            return true;
        } else {
            return false;
        }
    }
}
