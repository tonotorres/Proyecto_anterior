<?php

namespace App\Http\Controllers;

use App\Account;
use App\AddressBookDestination;
use App\AddressBookOption;
use App\ChangeLog;
use App\ListContactType;
use Illuminate\Http\Request;

class AddressBookDestinationsController extends Controller
{
    /**
     * @var int clave del módulo
     */
    private $module_key = 26;

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del cuenta indicado
     */
    private $module;

    public function api_get_destination($ddi, $callerid, $option = '') {
        $address_book_option = AddressBookOption::where('ddi', $ddi)
            ->where('option', $option)
            ->first();

        if(!empty($address_book_option)) {
            $json['redirect'] = $address_book_option->overflow.'';

            $list_contact_type = ListContactType::where('value', str_replace('+', '00', $callerid))
                ->where('contact_type_id', '1')
                ->first();

            if(!empty($list_contact_type)) {
                if($list_contact_type->module_key === 9) {
                    $accoun_address_book_destination = AddressBookDestination::where('address_book_id', $address_book_option->address_book_id)
                        ->where('module_id', '8')
                        ->where('reference_id', $list_contact_type->reference_id)
                        ->first();

                    if(!empty($accoun_address_book_destination)) {
                        $json['redirect'] = $accoun_address_book_destination->destination.'';
                    }
                }
            }
        } else {
            $json['redirect'] = '-1';    
        }
        //$json['redirect'] = '5990';
        return $json;
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
            $address_book_destination = self::create($request->all());

            if(empty($address_book_destination['errors'])) {
                return $address_book_destination->load('address_book');
            } else {
                return $address_book_destination;
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
            $address_book_destination = self::update($request->all(), $id);

            if(empty($address_book_destination['errors'])) {
                return $address_book_destination->load('address_book');
            } else {
                return $address_book_destination;
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
            $address_book_destination =  AddressBookDestination::create($data);

            $account = Account::where('id', $address_book_destination->reference_id)
                ->first();
            if (!empty($account)) {
                $label = $account->name;
            } else {
                $label = 'sin referencia';
            }

            $user = get_loged_user();
            $data_log['user_id'] = $user->id;
            $data_log['object'] = 'Account';
            $data_log['object_id'] = $address_book_destination->reference_id;
            $data_log['label'] = $label;
            $data_log['action'] = 'Create';
            $data_log['key'] = 'address_book_destination';
            $data_log['value'] = $address_book_destination->address_book->name . ' -> ' . $address_book_destination->destination;
            $data_log['date'] = strtotime('now');

            ChangeLog::create($data_log);

            return $address_book_destination;
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
            $address_book_destination = AddressBookDestination::findOrFail($id);

            $user = get_loged_user();

            $account = Account::where('id', $address_book_destination->reference_id)
                ->first();
            if (!empty($account)) {
                $label = $account->name;
            } else {
                $label = 'sin referencia';
            }

            $data_log['user_id'] = $user->id;
            $data_log['object'] = 'Account';
            $data_log['object_id'] = $address_book_destination->reference_id;
            $data_log['label'] = $label;
            $data_log['action'] = 'Update';
            $data_log['key'] = 'address_book_destination';
            $data_log['value'] = $address_book_destination->address_book->name . ' -> ' . $address_book_destination->destination;
            $data_log['date'] = strtotime('now');

            ChangeLog::create($data_log);

            $address_book_destination->update($data);

            return $address_book_destination;
        }
    }

    /**
     * @author Roger Corominas
     * Elimina el objeto
     * @param int $id Identificador de la cuenta
     * @return AddressBookDestination
     */
    private function delete (int $id) {
        $address_book_destination = AddressBookDestination::findOrFail($id);

        $user = get_loged_user();

        $account = Account::where('id', $address_book_destination->reference_id)
            ->first();
        if (!empty($account)) {
            $label = $account->name;
        } else {
            $label = 'sin referencia';
        }

        $data_log['user_id'] = $user->id;
        $data_log['object'] = 'Account';
        $data_log['object_id'] = $address_book_destination->reference_id;
        $data_log['label'] = $label;
        $data_log['action'] = 'Delete';
        $data_log['key'] = 'address_book_destination';
        $data_log['value'] = $address_book_destination->address_book->name . ' -> ' . $address_book_destination->destination;
        $data_log['date'] = strtotime('now');

        ChangeLog::create($data_log);

        $address_book_destination->delete();

        return $address_book_destination;
    }
}
