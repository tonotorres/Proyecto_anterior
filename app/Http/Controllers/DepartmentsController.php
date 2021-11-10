<?php

namespace App\Http\Controllers;

use App\Department;
use App\UserTemplateModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentsController extends Controller
{
    /**
     * @var int clave del módulo
     */
    private $module_key = 13;

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del cuenta indicado
     */
    private $module;

    /**
     * @author Roger Corominas
     * Devuelve un array con la información necesaria para un listado
     * @param Request $request
     * @return Department[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_list(Request $request) {
        $this->module = get_user_module_security($this->module_key);
        $user = get_loged_user();
        if(!empty($this->module->read)) {
            return Department::where('company_id', $user->company_id)
                ->select('id', 'name as label')
                ->get();
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Devuelve un array con todos los departamentos activos
     * @param Request $request
     * @return Department[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all(Request $request) {
        $this->module = get_user_module_security($this->module_key);
        $user = get_loged_user();
        if(!empty($this->module->read)) {
            return Department::where('company_id', $user->company_id)
                ->get();
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Devuelve un objeto con la información del departamento
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function api_get(int $id) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->read)) {
            if(self::validate_security($id)) {
                return Department::findOrFail($id);
            } else {
                return response()->json(['error' => 'unauthenticated'], 401);    
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta generamos un nuevo departamento, sino devolvemos los errores
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_store(Request $request) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->create)) {
            $department = self::create($request->all());

            if(empty($department['errors'])) {
                return $department;
            } else {
                return $department;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta actualizamos el departamento identificado por el parámetro $id, con la información facilitada
     * @param Request $request
     * @param int $id Identificador del tipo de cuenta
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_update(Request $request, int $id) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->update)) {
            if(self::validate_security($id)) {
                $department = self::update($request->all(), $id);

                if(empty($department['errors'])) {
                    return $department;
                } else {
                    return $department;
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
     * Eliminamos el departamento identificada por el campo Id
     * @param Request $request
     * @param int $id Identificador del departamento
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
     * Valida y genera un nuevo registro del departamento con los datos facilitados en $data
     * @param array $data Campos a introducir
     * @return array Devuelve el objeto generado o un array con los errores de validación
     */
    private function create (Array $data) {
        //RC: Si no tenemos empresa, añadimos la empresa por defecto
        if(empty($data['company_id'])) {
            $data['company_id'] = 1;
        }

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
            $depatrment = Department::create($data);

            $message_type_id = 1;
            $components[0]['id'] = $depatrment->id;
            $components[0]['type'] = 'department';

            create_chat_room($message_type_id, $depatrment->name, $components);

            return $depatrment;
        }
    }

    /**
     * @author Roger Corominas
     * Valida y actualiza el registro del departamento indentificado por $id con los datos del Array $data
     * @param array $data Campos a modificar
     * @param int $id Identificador del departamento
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
            $department = Department::findOrFail($id);

            $old_name = $department->name;
            $department->update($data);

            if($old_name != $department->name) {
                DB::update('UPDATE user_chat_room SET name = "'.$department->name.'" WHERE name = "'.$old_name.'"');
            }

            return $department;
        }
    }

    /**
     * @author Roger Corominas
     * Elimina el objeto
     * @param int $id Identificador del departamento
     * @return Department
     */
    private function delete (int $id) {
        $department = Department::findOrFail($id);

        $component['id'] = $department->id;
        $component['type'] = 'department';

        foreach($department->chat_rooms as $chat_room) {
            chat_room_remove_component($chat_room, $component);
        }
        $department->delete();

        return $department;
    }

    /**
     * @param integer $id Identificador del departamento
     * Devuelve si tenemos permiso para acceder a este elemento
     * @return bool 
     */
    private function validate_security(int $id) {
        $loged_user = get_loged_user();
        $department = Department::findOrFail($id);
        if($department->company_id == $loged_user->company_id) {
            return true;
        } else {
            return false;
        }
    }
}
