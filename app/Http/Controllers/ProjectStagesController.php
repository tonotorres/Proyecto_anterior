<?php

namespace App\Http\Controllers;

use App\ProjectStage;
use Illuminate\Http\Request;

class ProjectStagesController extends Controller
{
    /**
     * @var int clave del módulo
     */
    private $module_key = 46;

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del cuenta indicado
     */
    private $module;

    /**
     * @author Roger Corominas
     * Devuelve un array con todas las cuentas activos
     * @return ProjectStage[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all() {
        $this->module = get_user_module_security($this->module_key);
        $project_stages = self::get_all();
        if (!empty($project_stages)) {
            return $project_stages;
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_get_list()
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->list)) {
            $user = get_loged_user();
            return ProjectStage::select('id', 'name as label')
                ->where('company_id', $user->company_id)
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

        $project_stage = self::get($id);
        if (!empty($project_stage)) {
            return $project_stage;
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
            $project_stage = self::create($request->all());

            if(empty($project_stage['errors'])) {
                return $project_stage->load('project_stage_status');
            } else {
                return $project_stage;
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
            $project_stage = self::update($request->all(), $id);

            if(empty($project_stage['errors'])) {
                return $project_stage->load('project_stage_status');
            } else {
                return $project_stage;
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
            return ProjectStage::where('company_id', $user->company_id)->get()->load('project_stage_status');
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
            $project_stage = ProjectStage::findOrFail($id);

            if($project_stage->company_id != $user->company_id) {
                return null;
            } else {
                //RC: Si es de la misma compañía lo podemos devolver, en caso contrario no lo 
                return $project_stage->load('project_stage_status');
            }
        } else {
            return null;
        }

        if(!empty($this->module->read)) {
            return ProjectStage::findOrFail($id);
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
            $user = get_loged_user();
            $data['company_id'] = $user->company_id;
            return ProjectStage::create($data);
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
            $project_stage = ProjectStage::findOrFail($id);
            $user = get_loged_user();
            if($user->company_id == $project_stage->project->company_id) {
                $project_stage->update($data);
            }

            return $project_stage;
        }
    }

    /**
     * @author Roger Corominas
     * Elimina el objeto
     * @param int $id Identificador de la cuenta
     * @return ProjectStage
     */
    private function delete (int $id) {
        $project_stage = ProjectStage::findOrFail($id);
        $user = get_loged_user();
        if($user->company_id == $project_stage->project->company_id) {
            $project_stage->delete();
        }

        return $project_stage;
    }
}
