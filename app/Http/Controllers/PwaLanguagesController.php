<?php

namespace App\Http\Controllers;

use App\PwaLanguage;
use App\UserTemplateModule;
use Illuminate\Http\Request;

class PwaLanguagesController extends Controller
{
    /**
     * @var int clave del módulo
     */
    private $module_key = 11;

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del cuenta indicado
     */
    private $module;

    /**
     * @author Roger Corominas
     * PwaLanguagesController constructor.
     * Asignamos el objeto módulo al atributo module.
     */
    public function __construct() {
        $user_template_id = 1;
        $this->module = UserTemplateModule::generateQueryModuleByUserTempalateModuleKey($user_template_id, $this->module_key)
            ->first();
    }

    /**
     * @author Roger Corominas
     * Devuelve un array con todos los idiomas activos
     * @param Request $request
     * @return PwaLanguage[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all(Request $request) {
        if(!empty($this->module->read)) {
            return PwaLanguage::all();
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Devuelve un objeto con la información del idioma
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function api_get(int $id) {
        if(!empty($this->module->read)) {
            return PwaLanguage::findOrFail($id);
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta generamos un nuevo idioma, sino devolvemos los errores
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_store(Request $request) {
        if(!empty($this->module->create)) {
            $pwa_language = self::create($request->all());

            if(empty($pwa_language['errors'])) {
                return $pwa_language;
            } else {
                return $pwa_language;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta actualizamos el idioma identificada por el parámetro $id, con la información facilitada
     * @param Request $request
     * @param int $id Identificador del idioma
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_update(Request $request, int $id) {
        if(!empty($this->module->update)) {
            $pwa_language = self::update($request->all(), $id);

            if(empty($pwa_language['errors'])) {
                return $pwa_language;
            } else {
                return $pwa_language;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Eliminamos el idioma identificada por el campo Id
     * @param Request $request
     * @param int $id Identificador del idioma
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
     * Valida y genera un nuevo registro del idioma con los datos facilitados en $data
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
            //RC: si la validación fue correcta tenemos que generar el objeto
            return PwaLanguage::create($data);
        }
    }

    /**
     * @author Roger Corominas
     * Valida y actualiza el registro del idioma indentificado por $id con los datos del Array $data
     * @param array $data Campos a modificar
     * @param int $id Identificador del idioma
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
            $pwa_language = PwaLanguage::findOrFail($id);
            $pwa_language->update($data);

            return $pwa_language;
        }
    }

    /**
     * @author Roger Corominas
     * Elimina el objeto
     * @param int $id Identificador del idioma
     * @return PwaLanguage
     */
    private function delete (int $id) {
        $pwa_language = PwaLanguage::findOrFail($id);
        $pwa_language->delete();

        return $pwa_language;
    }
}
