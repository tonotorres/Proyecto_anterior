<?php

namespace App\Http\Controllers;

use App\Account;
use App\AccountContactType;
use App\ChangeLog;
use App\ListContactType;
use App\Tag;
use App\UserTemplateModule;
use Illuminate\Http\Request;

class AccountsController extends Controller
{
    /**
     * @var int $module_key clave del módulo
     * @var object $module  del módulo
     * @var int clave del módulo
     */
    private $module_key = 8;
    private $module;
    private $related_properties_list = ['emails', 'phones', 'tags'];
    private $related_properties = ['account_type', 'emails', 'phones', 'addresses', 'addresses.region', 'addresses.country', 'address_book_destinations', 'address_book_destinations.address_book', 'tags', 'subaccounts', 'parent_account', 'last_calls', 'last_calls.call_end', 'last_calls.call_users', 'last_calls.call_users.user', 'projects', 'projects.project_priority', 'projects.project_status', 'projects.project_description'];

    public function api_search_vselect(Request $request, $id = null)
    {
        $user = get_loged_user();
        $filter = $request->filter;

        if (!empty($filter)) {
            $accounts = Account::where('company_id', $user->company_id)
                ->where('name', 'like', '%' . $filter . '%')
                ->select('id', 'name as label')
                ->orderBy('name', 'asc')
                ->limit(50)
                ->get();
        } elseif (!empty($id)) {
            $accounts = Account::where('id', $id)
                ->where('company_id', $user->company_id)
                ->select('id', 'name as label')
                ->limit(1)
                ->get();
        } else {
            $accounts = [];
        }
        

        return $accounts;
    }

    public function api_search(Request $request, $page = 0)
    {
        $this->module = get_user_module_security($this->module_key);
        $user = get_loged_user();

        $accounts = Account::where('accounts.company_id', $user->company_id)
            ->select('accounts.*')
            ->distinct();

        if (!empty($request->name)) {
            $accounts->where(function ($query) use ($request) {
                $query->where('accounts.name', 'like', '%' . $request->name . '%')
                    ->orWhere('accounts.code', 'like', '%' . $request->name . '%');
            });
        }

        if (!empty($request->contact_type)) {
            $accounts->join('account_contact_types', 'account_contact_types.account_id', '=', 'accounts.id')
            ->where('account_contact_types.value', 'like', '%' . $request->contact_type . '%');
        }

        if (!empty($request->tag)) {
            $accounts->join('tag_module', function ($join) {
                $join->on('tag_module.reference_id', '=', 'accounts.id');
                $join->where('tag_module.module_key', '=', '8');
            })->join('tags', 'tags.id', '=', 'tag_module.tag_id')
            ->where('tags.name', 'like', '%' . $request->contact_type . '%');
        }

        $limit = $request->limit;
        $limit_start = ($page - 1) * $limit;

        if (!empty($request->sortColumn)) {
            $sortColumn = $request->sortColumn;
        } else {
            $sortColumn = 'name';
        }
        if ($request->sortDirection == 1) {
            $sortDirection = 'asc';
        } else {
            $sortDirection = 'desc';
        }

        $json['page'] = (int)$page;
        $json['limit'] = $limit;
        $json['limit_start'] = $limit_start;
        $json['total'] = $accounts->count('accounts.id');
        $json['total_pages'] = ceil($json['total'] / $limit);
        $json['data'] = $accounts
            ->orderBy($sortColumn, $sortDirection)
            ->limit($limit)
            ->offset($limit_start)
            ->get()
            ->load($this->related_properties_list);

        return $json;
    }

    /**
     * @author Roger Corominas
     * Devuelve un array con todas las cuentas activos
     * @return Account[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all() {
        $this->module = get_user_module_security($this->module_key);
        $accounts = self::get_all();
        if (!empty($accounts)) {
            return $accounts->load($this->related_properties_list);
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

        $account = self::get($id);
        if (!empty($account)) {
            return $account;
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
            $account = self::create($request->all());

            if (empty($account['errors '])) {
                return $account->load($this->related_properties);
            } else {
                return $account;
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
            $account = self::update($request->all(), $id);

            if (empty($account['errors '])) {
                return $account->load($this->related_properties);
            } else {
                return $account;
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

    public function api_import(Request $request)
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->create)) {
            return self::import_accounts($request->all());
        }
    }

    private function import_accounts($data)
    {
        $user = get_loged_user();

        if (!empty($data['str_to_import'])) {
            echo 'test';
            $lines = explode("EOL", $data['str_to_import']);

            if (!empty($lines)) {
                echo 'test2';
                $i = 0;
                print_r($lines);
                foreach ($lines as $line) {
                    $cols = explode("\t", $line);

                    print_r($cols);

                    if (!empty($cols[0])) {
                        $name = trim($cols[0]);

                        $account = Account::where('company_id', $user->company_id)
                            ->where('name', 'like', $name)
                            ->first();

                        if (empty($account)) {
                            $account = new Account();
                            $account->company_id = $user->company_id;
                            $account->name = $name;
                        }

                        if (!empty($cols[1])) {
                            $account->code = $cols[1];
                        }

                        if (!empty($cols[2])) {
                            $account->corporate_name = $cols[2];
                        }

                        if (!empty($cols[3])) {
                            $account->vat_number = $cols[3];
                        }

                        if (!empty($cols[4])) {
                            $account->contact = $cols[4];
                        }

                        if (!empty($cols[10])) {
                            $parent_account = Account::where('company_id', $user->company_id)
                                ->where('name', 'like', $cols[10])
                                ->first();

                            if (!empty($parent_account)) {
                                $account->account_id = $parent_account->id;
                            }
                        }

                        $account->save();

                        if (!empty($cols[6])) {
                            $phone = trim($cols[6]);
                            $phone = str_replace(' ', '', $phone);
                            $phone = str_replace('.', '', $phone);
                            $phone = str_replace('+', '00', $phone);

                            $account_contact_type = new AccountContactType();
                            $account_contact_type->contact_type_id = 1;
                            $account_contact_type->account_id = $account->id;
                            if (!empty($cols[7])) {
                                $account_contact_type->name = $cols[7];
                            }
                            $account_contact_type->value = $phone;
                            $account_contact_type->save();
                        }

                        if (!empty($cols[8])) {
                            $email = trim($cols[8]);
                            $email = str_replace(' ', '', $email);

                            $account_contact_type = new AccountContactType();
                            $account_contact_type->contact_type_id = 2;
                            $account_contact_type->account_id = $account->id;
                            if (!empty($cols[9])) {
                                $account_contact_type->name = $cols[9];
                            }
                            $account_contact_type->value = $email;
                            $account_contact_type->save();
                        }
                    }
                }
            }
        }
    }

    private function get_all() {
        //RC: Si no tenemos la seguridad del módulo lo volvemos a generar
        if (empty($this->module)) {
            $this->module = get_user_module_security($this->module_key);
        }

        if(!empty($this->module->read)) {
            $user = get_loged_user();
            return Account::where('company_id', $user->company_id)->get()->load($this->related_properties);
        } else {
            return response()->json(['error' => 'unauthenticated  '], 401);
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
            $account = Account::findOrFail($id)->load($this->related_properties);

            //RC: Si es de la misma compañía lo podemos devolver, en caso contrario no lo 
            if ($account->company_id == $user->company_id) {
                return $account;
            } else {
                return null;
            }
        } else {
            return null;
        }

        if(!empty($this->module->read)) {
            return Account::findOrFail($id)->load($this->related_properties);
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
            $user = get_loged_user();
            $data['company_id'] = $user->company_id;
            //RC: si la validación fue correcta tenemos que generar el objeto
            $account = Account::create($data);

            if(!empty($data['tags_id'])) {
                $tags = [];
                foreach($data['tags_id'] as $tag) {
                    $tags[$tag] = ['module_key' => $this->module_key];
                }

                $account->tags()->sync($tags);
            }

            $user = get_loged_user();
            $data_log['user_id'] = $user->id;
            $data_log['object'] = 'Account';
            $data_log['object_id'] = $account->id;
            $data_log['label'] = $account->name;
            $data_log['action'] = 'Create';
            $data_log['key'] = '';
            $data_log['value'] = '';
            $data_log['date'] = strtotime('now');

            ChangeLog::create($data_log);

            return $account;
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
            $account = Account::findOrFail($id);

            $user = get_loged_user();
            if($user->company_id == $account->company_id) {
                //RC: Obtenemos todos los cambios
                $changes = self::getSaveChanges($data, $account);
                foreach ($changes as $key => $value) {
                    $data_log['user_id'] = $user->id;
                    $data_log['object'] = 'Account';
                    $data_log['object_id'] = $id;
                    $data_log['label'] = $account->name;
                    $data_log['action'] = 'Update';
                    $data_log['key'] = $key;
                    $data_log['value'] = $value;
                    $data_log['date'] = strtotime('now');

                    ChangeLog::create($data_log);
                }
                $account->update($data);

                //RC: generamos el registro en la tabla de lista
                ListContactType::where('module_key', '9')
                    ->where('reference_id', $account->id)
                    ->update(['name' => $account->name]);

                if(!empty($data['tags_id'])) {
                    $tags = [];
                    foreach($data['tags_id'] as $tag) {
                        $tags[$tag] = ['module_key' => $this->module_key];
                    }
    
                    $account->tags()->sync($tags);
                } else {
                    $account->tags()->detach();
                }
            }

            return $account;
        }
    }

    /**
     * @author Roger Corominas
     * Elimina el objeto
     * @param int $id Identificador de la cuenta
     * @return Account
     */
    private function delete (int $id) {
        $account = Account::findOrFail($id);
        $user = get_loged_user();
        $data_log['user_id'] = $user->id;
        $data_log['object'] = 'Account';
        $data_log['object_id'] = $id;
        $data_log['label'] = $account->name;
        $data_log['action'] = 'Delete';
        $data_log['key'] = '';
        $data_log['value'] = '';
        $data_log['date'] = strtotime('now');

        ChangeLog::create($data_log);

        if($account->company_id == $user->company_id) {
            ListContactType::where('module_key', '9')
                ->where('reference_id', $account->id)
                ->delete();

            foreach($account->contacts as $contact) {
                $contact->emails()->delete();
                $contact->phones()->delete();

                ListContactType::where('module_key', '7')
                    ->where('reference_id', $contact->id)
                    ->delete();

                $contact->delete();
            }

            $account->emails()->delete();
            $account->phones()->delete();
            $account->delete();
        }   

        return $account;
    }

    private function getSaveChanges($data, $account)
    {
        $changes = [];
        //RC: Validamos todos los objetos del account
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                if (isset($account->$key) && $account->$key != $value) {
                    $changes[$key] = $value;
                }
            }
        }

        //RC: Validamos las etiquetas
        $currents_tags = [];
        foreach ($account->tags as $tag) {
            $currents_tags[] = $tag->id;
        }

        $diff1 = array_diff($data['tags_id'], $currents_tags);
        $diff2 = array_diff($currents_tags, $data['tags_id']);

        if (count($diff1) > 0 || count($diff2) > 0) {
            $changes['tags'] = '';

            if (!empty($diff1)) {
                $changes['tags'] .= 'Añadimos: ';
                foreach ($diff1 as $tag_id) {
                    $tag = Tag::findOrFail($tag_id);
                    $changes['tags'] .= $tag->name . ', ';
                }
            }

            if (!empty($diff2)) {
                $changes['tags'] .= 'Eliminamos: ';
                foreach ($diff2 as $tag_id) {
                    $tag = Tag::findOrFail($tag_id);
                    $changes['tags'] .= $tag->name . ', ';
                }
            }
        }

        return $changes;
    }
}
