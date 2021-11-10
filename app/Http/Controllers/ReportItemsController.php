<?php

namespace App\Http\Controllers;

use App\ReportItem;
use Illuminate\Http\Request;

class ReportItemsController extends Controller
{
    /**
     * @var int clave del módulo
     */
    private $module_key = 37;

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del cuenta indicado
     */
    private $module;

    /**
     * @author Roger Corominas
     * Devuelve un array con todos los report_itemos activos
     * @param Request $request
     * @return ReportItem[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all(Request $request) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->read)) {
            return ReportItem::all();
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Devuelve un objeto con la información del report_itemo
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function api_get(int $report_id, int $id) {
        $this->module = get_user_module_security($this->module_key);
        $user = get_loged_user();

        if(!empty($this->module->read)) {
            $report_item = ReportItem::findOrFail($id);
            if($report_item->report->company_id == $user->company_id) {
                return $report_item;
            } else {
                return response()->json(['error' => 'unauthenticated'], 401);    
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta generamos un nuevo report_itemo, sino devolvemos los errores
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_store(Request $request, int $report_id) {
        $this->module = get_user_module_security($this->module_key);
        if(!empty($this->module->create)) {
            $report_item = self::create($request->all(), $report_id);

            if(empty($report_item['errors'])) {
                return $report_item;
            } else {
                return $report_item;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta actualizamos el report_itemo identificado por el parámetro $id, con la información facilitada
     * @param Request $request
     * @param int $id Identificador del tipo de cuenta
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_update(Request $request, int $report_id, int $id) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->update)) {
            $report_item = self::update($request->all(), $report_id, $id);

            if(empty($report_item['errors'])) {
                return $report_item;
            } else {
                return $report_item;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Eliminamos el report_itemo identificada por el campo Id
     * @param Request $request
     * @param int $id Identificador del tipo de cuenta
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function api_delete(Request $request, int $report_id, int $id) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->delete)) {
            return self::delete($id);
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Valida y genera un nuevo registro del report_itemo con los datos facilitados en $data
     * @param array $data Campos a introducir
     * @return array Devuelve el objeto generado o un array con los errores de validación
     */
    private function create (Array $data, $report_id) {
        //RC: Obtenemos las validaciones
        $validations = get_user_template_fields_validations($this->module->user_template_id, $this->module->module_id);

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.
            return ['errors' => $validator->errors()];
        } else {
            //RC: si la validación fue correcta tenemos que generar el objeto
            $data['report_id'] = $report_id;
            $report_item = ReportItem::create($data);

            return $report_item;
        }
    }

    /**
     * @author Roger Corominas
     * Valida y actualiza el registro del report_itemo indentificado por $id con los datos del Array $data
     * @param array $data Campos a modificar
     * @param int $id Identificador del report_itemo
     * @return array Devuelve el objeto actualizado o un array con los errores de validación.
     */
    private function update (Array $data, int $report_id, int $id) {
        //RC: Obtenemos las validaciones
        $validations = get_user_template_fields_validations($this->module->user_template_id, $this->module->module_id, $id);

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.
            return ['errors' => $validator->errors()];
        } else {
            //RC: si la validación fue correcta tenemos que generar el objeto
            $report_item = ReportItem::findOrFail($id);
            $user = get_loged_user();

            if($report_item->report->company_id == $user->company_id) {
                $report_item->update($data);
                return $report_item;
            } else {
                return ['errors' => true];
            }
        }
    }

    /**
     * @author Roger Corominas
     * Elimina el objeto
     * @param int $id Identificador del report_itemo
     * @return ReportItem
     */
    private function delete (int $id) {
        $report_item = ReportItem::findOrFail($id);
        $report_item->delete();

        return $report_item;
    }
}
