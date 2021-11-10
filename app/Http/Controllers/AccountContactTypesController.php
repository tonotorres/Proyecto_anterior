<?php

namespace App\Http\Controllers;

use App\AccountContactType;
use App\ListContactType;
use App\UserTemplateModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountContactTypesController extends Controller
{
    /**
     * @var int clave del módulo
     */
    private $module_key = 9;

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del cuenta indicado
     */
    private $module;

    /**
     * @author Roger Corominas
     * Devuelve un array con todos los account_contact_typeos activos
     * @param Request $request
     * @return AccountContactType[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all(Request $request) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->read)) {
            return AccountContactType::all();
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Devuelve un objeto con la información del account_contact_typeo
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function api_get(int $id) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->read)) {
            return AccountContactType::findOrFail($id);
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta generamos un nuevo account_contact_typeo, sino devolvemos los errores
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_store(Request $request) {
        $this->module = get_user_module_security($this->module_key);
        if(!empty($this->module->create)) {
            $account_contact_type = self::create($request->all());

            if(empty($account_contact_type['errors'])) {
                return $account_contact_type;
            } else {
                return $account_contact_type;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta actualizamos el account_contact_typeo identificado por el parámetro $id, con la información facilitada
     * @param Request $request
     * @param int $id Identificador del tipo de cuenta
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_update(Request $request, int $id) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->update)) {
            $account_contact_type = self::update($request->all(), $id);

            if(empty($account_contact_type['errors'])) {
                return $account_contact_type;
            } else {
                return $account_contact_type;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Eliminamos el account_contact_typeo identificada por el campo Id
     * @param Request $request
     * @param int $id Identificador del tipo de cuenta
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

    /**
     * @author Roger Corominas
     * Valida y genera un nuevo registro del account_contact_typeo con los datos facilitados en $data
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
            $account_contact_type = AccountContactType::create($data);

            //RC: Actualizar las llamadas con ese teléfono
            DB::update("UPDATE calls SET account_id = " . $account_contact_type->account_id . " WHERE company_id = " . $account_contact_type->account->company_id . " AND (calls.from = '" . $account_contact_type->value . "' OR calls.to = '" . $account_contact_type->value . "')");

            //RC: generamos el registro en la tabla de lista
            $list_contact_type = new ListContactType();
            $list_contact_type->company_id = $account_contact_type->account->company_id;
            $list_contact_type->module_key = $this->module_key;
            $list_contact_type->contact_type_id = $account_contact_type->contact_type_id;
            if(!empty($account_contact_type->account->code)) {
                $list_contact_type->name = $account_contact_type->account->code.' '.$account_contact_type->account->name;
            } else {
                $list_contact_type->name = $account_contact_type->account->name;
            }
            $list_contact_type->value = $account_contact_type->value;
            $list_contact_type->reference_type_id = $account_contact_type->id;
            $list_contact_type->reference_id = $account_contact_type->account_id;
            $list_contact_type->save();

            return $account_contact_type;
        }
    }

    /**
     * @author Roger Corominas
     * Valida y actualiza el registro del account_contact_typeo indentificado por $id con los datos del Array $data
     * @param array $data Campos a modificar
     * @param int $id Identificador del account_contact_typeo
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
            $account_contact_type = AccountContactType::findOrFail($id);
            $account_contact_type->update($data);

            //RC: generamos el registro en la tabla de lista
            $list_contact_type = ListContactType::where('module_key', $this->module_key)
                ->where('contact_type_id', $account_contact_type->contact_type_id)
                ->where('reference_type_id', $account_contact_type->id)
                ->where('reference_id', $account_contact_type->account_id)
                ->first();

            if(empty($list_contact_type)) {
                $list_contact_type = new ListContactType();
                $list_contact_type->company_id = $account_contact_type->account->company_id;
                $list_contact_type->module_key = $this->module_key;
                $list_contact_type->contact_type_id = $account_contact_type->contact_type_id;
                $list_contact_type->reference_type_id = $account_contact_type->id;
                $list_contact_type->reference_id = $account_contact_type->account_id;
            }

            if(!empty($account_contact_type->account->code)) {
                $list_contact_type->name = $account_contact_type->account->code.' '.$account_contact_type->account->name;
            } else {
                $list_contact_type->name = $account_contact_type->account->name;
            }
            $list_contact_type->value = $account_contact_type->value;
            $list_contact_type->save();

            return $account_contact_type;
        }
    }

    /**
     * @author Roger Corominas
     * Elimina el objeto
     * @param int $id Identificador del account_contact_typeo
     * @return AccountContactType
     */
    private function delete (int $id) {
        $account_contact_type = AccountContactType::findOrFail($id);
        $account_contact_type->delete();

        //RC: generamos el registro en la tabla de lista
        ListContactType::where('module_key', $this->module_key)
            ->where('reference_type_id', $id)
            ->where('reference_id', $account_contact_type->account_id)
            ->delete();

        return $account_contact_type;
    }
}
