<?php

namespace App\Http\Controllers;

use App\IvrOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IvrOptionsController extends Controller
{
    /**
     * @var int clave del módulo
     */
    private $module_key = 39;

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del cuenta indicado
     */
    private $module;

    /**
     * @author Roger Corominas
     * Devuelve un array con todos los ivr_optionos activos
     * @param Request $request
     * @return IvrOption[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all(Request $request) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->read)) {
            return IvrOption::all()->load('tag');
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Devuelve un objeto con la información del ivr_optiono
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function api_get(int $ivr_id, int $id) {
        $this->module = get_user_module_security($this->module_key);
        $user = get_loged_user();

        if(!empty($this->module->read)) {
            $ivr_option = IvrOption::findOrFail($id);
            if($ivr_option->ivr->company_id == $user->company_id) {
                return $ivr_option->load('tag');
            } else {
                return response()->json(['error' => 'unauthenticated'], 401);    
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta generamos un nuevo ivr_optiono, sino devolvemos los errores
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_store(Request $request, int $ivr_id) {
        $this->module = get_user_module_security($this->module_key);
        if(!empty($this->module->create)) {
            $ivr_option = self::create($request->all(), $ivr_id);

            if(empty($ivr_option['errors'])) {
                return $ivr_option->load('tag');
            } else {
                return $ivr_option;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta actualizamos el ivr_optiono identificado por el parámetro $id, con la información facilitada
     * @param Request $request
     * @param int $id Identificador del tipo de cuenta
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_update(Request $request, int $ivr_id, int $id) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->update)) {
            $ivr_option = self::update($request->all(), $ivr_id, $id);

            if(empty($ivr_option['errors'])) {
                return $ivr_option->load('tag');
            } else {
                return $ivr_option;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Eliminamos el ivr_optiono identificada por el campo Id
     * @param Request $request
     * @param int $id Identificador del tipo de cuenta
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function api_delete(Request $request, int $ivr_id, int $id) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->delete)) {
            return self::delete($id);
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Valida y genera un nuevo registro del ivr_optiono con los datos facilitados en $data
     * @param array $data Campos a introducir
     * @return array Devuelve el objeto generado o un array con los errores de validación
     */
    private function create (Array $data, $ivr_id) {
        //RC: Obtenemos las validaciones
        $validations = get_user_template_fields_validations($this->module->user_template_id, $this->module->module_id);

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.
            return ['errors' => $validator->errors()];
        } else {
            //RC: si la validación fue correcta tenemos que generar el objeto
            $data['ivr_id'] = $ivr_id;
            $ivr_option = IvrOption::create($data);

            if(!empty($ivr_option->ivr->pbx_id) && !empty($ivr_option->tag_id)) {
                DB::update("UPDATE call_ivrs INNER JOIN calls ON call_ivrs.call_id = calls.id SET call_ivrs.ivr_option_tag_id = ".$ivr_option->tag_id." WHERE calls.company_id = ".$ivr_option->ivr->company_id." AND call_ivrs.pbx_ivr = ".$ivr_option->ivr->pbx_id. " AND call_ivrs.option = '".$ivr_option->option."' AND call_ivrs.ivr_option_tag_id IS NULL");
            }

            return $ivr_option;
        }
    }

    /**
     * @author Roger Corominas
     * Valida y actualiza el registro del ivr_optiono indentificado por $id con los datos del Array $data
     * @param array $data Campos a modificar
     * @param int $id Identificador del ivr_optiono
     * @return array Devuelve el objeto actualizado o un array con los errores de validación.
     */
    private function update (Array $data, int $ivr_id, int $id) {
        //RC: Obtenemos las validaciones
        $validations = get_user_template_fields_validations($this->module->user_template_id, $this->module->module_id, $id);

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.
            return ['errors' => $validator->errors()];
        } else {
            //RC: si la validación fue correcta tenemos que generar el objeto
            $ivr_option = IvrOption::findOrFail($id);
            $user = get_loged_user();

            if($ivr_option->ivr->company_id == $user->company_id) {
                $ivr_option->update($data);

                if(!empty($ivr_option->ivr->pbx_id) && !empty($ivr_option->tag_id)) {
                    DB::update("UPDATE call_ivrs INNER JOIN calls ON call_ivrs.call_id = calls.id SET call_ivrs.ivr_option_tag_id = ".$ivr_option->tag_id." WHERE calls.company_id = ".$ivr_option->ivr->company_id." AND call_ivrs.pbx_ivr = ".$ivr_option->ivr->pbx_id. " AND call_ivrs.option = '".$ivr_option->option."' AND call_ivrs.ivr_option_tag_id IS NULL");
                }
                
                return $ivr_option;
            } else {
                return ['errors' => true];
            }
        }
    }

    /**
     * @author Roger Corominas
     * Elimina el objeto
     * @param int $id Identificador del ivr_optiono
     * @return IvrOption
     */
    private function delete (int $id) {
        $ivr_option = IvrOption::findOrFail($id);
        $ivr_option->delete();

        return $ivr_option;
    }
}
