<?php

namespace App\Http\Controllers;

use App\Text;
use App\UserTemplateModule;
use Illuminate\Http\Request;

class TextsController extends Controller
{
    /**
     * @var int clave del módulo
     */
    private $module_key = 18;

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del cuenta indicado
     */
    private $module;

    /**
     * @author Roger Corominas
     * TextsController constructor.
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
     * @return Text[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all(Request $request) {
        if(!empty($this->module->read)) {
            return Text::all();
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
            return Text::findOrFail($id);
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
            $text = self::create($request->all());

            if(empty($text['errors'])) {
                return $text;
            } else {
                return $text;
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
            $text = self::update($request->all(), $id);

            if(empty($text['errors'])) {
                return $text;
            } else {
                return $text;
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
            return Text::create($data);
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
            $text = Text::findOrFail($id);
            $text->update($data);

            return $text;
        }
    }

    /**
     * @author Roger Corominas
     * Elimina el objeto
     * @param int $id Identificador del idioma
     * @return Text
     */
    private function delete (int $id) {
        $text = Text::findOrFail($id);
        $text->delete();

        return $text;
    }
}
