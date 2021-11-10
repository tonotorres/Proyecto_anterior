<?php

namespace App\Http\Controllers;

use App\ContactContactType;
use App\ListContactType;
use App\UserTemplateModule;
use Illuminate\Http\Request;

class ContactContactTypesController extends Controller
{
    /**
     * @var int clave del módulo
     */
    private $module_key = 7;

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del cuenta indicado
     */
    private $module;

    /**
     * @author Roger Corominas
     * ContactContactTypesController constructor.
     * Asignamos el objeto módulo al atributo module.
     */
    public function __construct() {
        $user_template_id = 1;
        $this->module = UserTemplateModule::generateQueryModuleByUserTempalateModuleKey($user_template_id, $this->module_key)
            ->first();
    }

    /**
     * @author Roger Corominas
     * Devuelve un array con todos los contact_contact_typeos activos
     * @param Request $request
     * @return ContactContactType[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all(Request $request) {
        if(!empty($this->module->read)) {
            return ContactContactType::all();
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Devuelve un objeto con la información del contact_contact_typeo
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function api_get(int $id) {
        if(!empty($this->module->read)) {
            return ContactContactType::findOrFail($id);
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta generamos un nuevo contact_contact_typeo, sino devolvemos los errores
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_store(Request $request) {
        if(!empty($this->module->create)) {
            $contact_contact_type = self::create($request->all());

            if(empty($contact_contact_type['errors'])) {
                return $contact_contact_type;
            } else {
                return $contact_contact_type;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta actualizamos el contact_contact_typeo identificado por el parámetro $id, con la información facilitada
     * @param Request $request
     * @param int $id Identificador del tipo de cuenta
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_update(Request $request, int $id) {
        if(!empty($this->module->update)) {
            $contact_contact_type = self::update($request->all(), $id);

            if(empty($contact_contact_type['errors'])) {
                return $contact_contact_type;
            } else {
                return $contact_contact_type;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Eliminamos el contact_contact_typeo identificada por el campo Id
     * @param Request $request
     * @param int $id Identificador del tipo de cuenta
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
     * Valida y genera un nuevo registro del contact_contact_typeo con los datos facilitados en $data
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
            $contact_contact_type = ContactContactType::create($data);

            //RC: generamos el registro en la tabla de lista
            $list_contact_type = new ListContactType();
            $list_contact_type->company_id = $contact_contact_type->contact->company_id;
            $list_contact_type->module_key = $this->module_key;
            $list_contact_type->contact_type_id = $contact_contact_type->contact_type_id;
            $list_contact_type->name = $contact_contact_type->contact->name;
            $list_contact_type->value = $contact_contact_type->value;
            $list_contact_type->reference_type_id = $contact_contact_type->id;
            $list_contact_type->reference_id = $contact_contact_type->contact_id;
            $list_contact_type->save();

            return $contact_contact_type;
        }
    }

    /**
     * @author Roger Corominas
     * Valida y actualiza el registro del contact_contact_typeo indentificado por $id con los datos del Array $data
     * @param array $data Campos a modificar
     * @param int $id Identificador del contact_contact_typeo
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
            $contact_contact_type = ContactContactType::findOrFail($id);
            $contact_contact_type->update($data);

            //RC: generamos el registro en la tabla de lista
            $list_contact_type = ListContactType::where('module_key', $this->module_key)
                ->where('contact_type_id', $contact_contact_type->contact_type_id)
                ->where('reference_type_id', $contact_contact_type->id)
                ->where('reference_id', $contact_contact_type->contact_id)
                ->first();

            if(empty($list_contact_type)) {
                $list_contact_type = new ListContactType();
                $list_contact_type->company_id = $contact_contact_type->contact->company_id;
                $list_contact_type->module_key = $this->module_key;
                $list_contact_type->reference_type_id = $contact_contact_type->id;
                $list_contact_type->contact_type_id = $contact_contact_type->contact_type_id;
            }

            $list_contact_type->name = $contact_contact_type->contact->name;
            $list_contact_type->value = $contact_contact_type->value;
            $list_contact_type->reference_id = $contact_contact_type->contact_id;
            $list_contact_type->save();

            return $contact_contact_type;
        }
    }

    /**
     * @author Roger Corominas
     * Elimina el objeto
     * @param int $id Identificador del contact_contact_typeo
     * @return ContactContactType
     */
    private function delete (int $id) {
        $contact_contact_type = ContactContactType::findOrFail($id);
        $contact_contact_type->delete();

        //RC: generamos el registro en la tabla de lista
        ListContactType::where('module_key', $this->module_key)
            ->where('reference_type_id', $id)
            ->where('reference_id', $contact_contact_type->contact_id)
            ->delete();

        return $contact_contact_type;
    }
}
