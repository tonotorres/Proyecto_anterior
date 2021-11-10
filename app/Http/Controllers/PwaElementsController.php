<?php

namespace App\Http\Controllers;

use App\PwaElement;
use App\UserTemplateModule;
use Illuminate\Http\Request;

class PwaElementsController extends Controller
{
    /**
     * @var int clave del módulo
     */
    private $module_key = 12;

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del cuenta indicado
     */
    private $module;

    /**
     * @author Roger Corominas
     * PwaElementsController constructor.
     * Asignamos el objeto módulo al atributo module.
     */
    public function __construct() {
        $user_template_id = 1;
        $this->module = UserTemplateModule::generateQueryModuleByUserTempalateModuleKey($user_template_id, $this->module_key)
            ->first();
    }

    /**
     * @author Roger Corominas
     * Devuelve un array con todos los elementos activos
     * @param Request $request
     * @return PwaElement[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all(Request $request) {
        if(!empty($this->module->read)) {
            return PwaElement::all();
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Devuelve un objeto con la información del elemento
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function api_get(int $id) {
        if(!empty($this->module->read)) {
            return PwaElement::findOrFail($id)->load('pwa_element_type', 'pwa_language');
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta generamos un nuevo elemento, sino devolvemos los errores
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_store(Request $request) {
        if(!empty($this->module->create)) {
            $pwa_element = self::create($request->all());

            if(empty($pwa_element['errors'])) {
                $pwa_element->content = json_decode($pwa_element->content);
                return $pwa_element->load('pwa_element_type', 'pwa_language');
            } else {
                return $pwa_element;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta actualizamos el elemento identificada por el parámetro $id, con la información facilitada
     * @param Request $request
     * @param int $id Identificador del elemento
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_update(Request $request, int $id) {
        if(!empty($this->module->update)) {
            $pwa_element = self::update($request->all(), $id);

            if(empty($pwa_element['errors'])) {
                $pwa_element->content = json_decode($pwa_element->content);
                return $pwa_element->load('pwa_element_type', 'pwa_language');
            } else {
                return $pwa_element;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Eliminamos el elemento identificada por el campo Id
     * @param Request $request
     * @param int $id Identificador del elemento
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function api_delete(Request $request, int $id) {
        if(!empty($this->module->delete)) {
            return self::delete($id);
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Valida y genera un nuevo registro del elemento con los datos facilitados en $data
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
            $data['content'] = json_encode($data['content']);
            //RC: si la validación fue correcta tenemos que generar el objeto
            return PwaElement::create($data);
        }
    }

    /**
     * @author Roger Corominas
     * Valida y actualiza el registro del elemento indentificado por $id con los datos del Array $data
     * @param array $data Campos a modificar
     * @param int $id Identificador del elemento
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
            $pwa_element = PwaElement::findOrFail($id);
            $data['content'] = json_encode($data['content']);
            $pwa_element->update($data);

            return $pwa_element;
        }
    }

    /**
     * @author Roger Corominas
     * Elimina el objeto
     * @param int $id Identificador del elemento
     * @return PwaElement
     */
    private function delete (int $id) {
        $pwa_element = PwaElement::findOrFail($id);
        $pwa_element->delete();

        return $pwa_element;
    }
}
