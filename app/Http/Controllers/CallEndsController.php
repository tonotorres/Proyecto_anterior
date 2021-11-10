<?php

namespace App\Http\Controllers;

use App\CallEnd;
use Illuminate\Http\Request;

class CallEndsController extends Controller
{
    /**
     * @var int clave del módulo
     */
    private $module_key = 29;

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del cuenta indicado
     */
    private $module;

    /**
     * @author Roger Corominas
     * Devuelve un array con todas las cuentas activos
     * @return CallEnd[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all() {
        $this->module = get_user_module_security($this->module_key);
        $call_ends = self::get_all();
        if (!empty($call_ends)) {
            return $call_ends;
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_get_list()
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->read)) {
            $user = get_loged_user();
            return CallEnd::join('call_end_department', 'call_end_department.call_end_id', '=', 'call_ends.id')
                ->distinct()
                ->select('id', 'name as label')
                ->where('company_id', $user->company_id)
                ->where('call_end_department.department_id', $user->department_id)
                ->orderBy('position', 'asc')
                ->orderBy('name', 'asc')
                ->get();
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
    public function api_get(int $id) {
        $this->module = get_user_module_security($this->module_key);

        $call_end = self::get($id);
        if (!empty($call_end)) {
            return $call_end;
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
    public function api_store(Request $request) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->create)) {
            $call_end = self::create($request->all());

            if(empty($call_end['errors'])) {
                return $call_end->load('call_types', 'departments');
            } else {
                return $call_end;
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
    public function api_update(Request $request, int $id) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->update)) {
            $call_end = self::update($request->all(), $id);

            if(empty($call_end['errors'])) {
                return $call_end->load('call_types', 'departments');
            } else {
                return $call_end;
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
    public function api_delete(Request $request, int $id) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->delete)) {
            return self::delete($id);
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    private function get_all() {
        //RC: Si no tenemos la seguridad del módulo lo volvemos a generar
        if (empty($this->module)) {
            $this->module = get_user_module_security($this->module_key);
        }

        if(!empty($this->module->read)) {
            $user = get_loged_user();
            return CallEnd::where('company_id', $user->company_id)->get()->load('call_types', 'departments');
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    private function get($id) {
        //RC: Si no tenemos la seguridad del módulo lo volvemos a generar
        if (empty($this->module)) {
            $this->module = get_user_module_security($this->module_key);
        }

        //RC: Miramos si tenemos permisos para leer el objecto
        if (!empty($this->module->read)) {
            $user = get_loged_user();
            $call_end = CallEnd::findOrFail($id);
            if($user->company_id == $call_end->company_id) {
                return $call_end->load('call_types', 'departments');
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * @author Roger Corominas
     * Valida y genera un nuevo registro de la cuenta con los datos facilitados en $data
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
            $call_end = CallEnd::create($data);

            if(!empty($data['call_types_id'])) {
                $call_end->call_types()->sync($data['call_types_id']);
            }

            if(!empty($data['departments_id'])) {
                $call_end->departments()->sync($data['departments_id']);
            }

            return $call_end;
        }
    }

    /**
     * @author Roger Corominas
     * Valida y actualiza el registro de la cuenta indentificado por $id con los datos del Array $data
     * @param array $data Campos a modificar
     * @param int $id Identificador de la cuenta
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
            $user = get_loged_user();
            $call_end = CallEnd::findOrFail($id);
            
            if($user->company_id == $call_end->company_id) {
                $call_end->update($data);

                if(!empty($data['call_types_id'])) {
                    $call_end->call_types()->sync($data['call_types_id']);
                } else {
                    $call_end->call_types()->detach();
                }
    
                if(!empty($data['departments_id'])) {
                    $call_end->departments()->sync($data['departments_id']);
                } else {
                    $call_end->departments()->detach();
                }
            }

            return $call_end;
        }
    }

    /**
     * @author Roger Corominas
     * Elimina el objeto
     * @param int $id Identificador de la cuenta
     * @return CallEnd
     */
    private function delete (int $id) {
        $user = get_loged_user();
        $call_end = CallEnd::findOrFail($id);
        
        if($user->company_id == $call_end->company_id) {
            $call_end->delete();
        }

        return $call_end;
    }
}
