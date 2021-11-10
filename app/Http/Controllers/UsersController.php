<?php

namespace App\Http\Controllers;

use App\BreakTimeUser;
use App\CompanyConfig;
use App\CurrentCall;
use App\CurrentCallUser;
use App\Department;
use App\Events\UserChangeExtension;
use App\Events\UserKeepAlive;
use App\User;
use App\UserCustom;
use App\UserExtension;
use App\UserSession;
use App\UserTemplateModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{
    /**
     * @var int clave del módulo
     */
    private $module_key = 4;

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del usuario indicado
     */
    private $module;

    /**
     * @author Roger Corominas
     * Función que actualiza la hora de la última conexión y genera un evento para actualizar la información en el panel
     * @return App\User
     */
    public function api_keep_alive()
    {
        $user = get_loged_user();
        $user->keep_alive_at = date('Y-m-d H:i:s');
        $user->save();

        return $user->load('user_extensions', 'user_extensions.original_extension', 'active_session', 'campaigns');
    }

    public function api_change_exension($new_extension) {
        $user = get_loged_user();

        if(empty($user->extension) || $user->extension != $new_extension) {
            $user_status = get_user_status($user);
            pause_all_extension($user->extension);

            $user->extension = $new_extension;

            $user->save();
            switch($user_status['status_type']) {
                case 'break_time':
                    if(!empty($user->extension)) {
                        pause_all_extension($user->extension);
                    }
                break;
                default:
                    unpause_all_extension($user->extension);
                break;
            }

            //RC: Si tenemos una extensión en la sesion actual la tenemos que finalizar y generar otra con la nueva extensión
            self::updateActiveSessionExtension($user->extension);

            broadcast(new UserChangeExtension($user));
        } else {
            if (empty($user->active_session->extension)) {
                self::updateActiveSessionExtension($user->extension);
            }
        }

        return $user->load('user_extensions', 'user_extensions.original_extension', 'active_session', 'campaigns');
    }

    public function api_get_users_statuses() {
        $user = get_loged_user();
        $users = User::where('company_id', $user->company_id)
            ->orderBy('name', 'asc')
            ->get();

        $users_statuses = [];
        foreach($users as $user) {
            $user_row = get_user_status($user);

            $users_statuses[] = $user_row;
        }

        return $users_statuses;
    }
    /**
     * @author Roger Corominas
     * Actualiza la empresa seleccionada del usuario en caso de estar disponible
     * @param integer $company_id identificador de la compañía
     * @return App\User
     */
    public function api_set_company_id(int $company_id) {
        $user = get_loged_user();

        if($user->companies()->where('id', $company_id)->count() > 0) {
            $user->company_id = $company_id;
            $user->save();
        }

        return $user;
    }

    /**
     * @author Roger Corominas
     * Devuelve un array con la información necesaria para un listado
     * @param Request $request
     * @return User[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_list(Request $request) {
        $user = get_loged_user();
        $this->module = get_user_module_security($this->module_key);
        
        if(!empty($this->module->read)) {
            return User::getCompanyUsers($user->company_id)
                ->select('id', 'name as label')
                ->get();
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Devuelve un array con todos los tipos de usuario activos
     * @param Request $request
     * @return User[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all(Request $request) {
        $user = get_loged_user();
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->read)) {
            return User::getCompanyUsers($user->company_id)
                ->getUserCustoms()
                ->get()
                ->load('companies', 'user_type', 'user_template', 'user_extensions');
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Devuelve un objeto con la información del tipo de usuario
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function api_get(int $id) {
        $user = get_loged_user();
        $this->module = get_user_module_security($this->module_key);

        if(self::validate_security($id)) {
            if(!empty($this->module->read)) {
                return User::getUserCustoms()
                    ->where('users.id', $id)
                    ->first()
                    ->load('companies', 'user_type', 'user_template', 'user_extensions');
            } else {
                return response()->json(['error' => 'unauthenticated'], 401);
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta generamos un nuevo tipo de usuario, sino devolvemos los errores
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_store(Request $request) {
        $this->module = get_user_module_security($this->module_key);
        
        if(!empty($this->module->create)) {
            $user = self::create($request->all());

            if(empty($user['errors'])) {
                return $user->load('companies', 'user_type', 'user_template', 'user_extensions');
            } else {
                return $user;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta actualizamos el tipo de usuario identificado por el parámetro $id, con la información facilitada
     * @param Request $request
     * @param int $id Identificador del tipo de usuario
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_update(Request $request, int $id) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->update)) {
            if(self::validate_security($id)) {
                $user = self::update($request->all(), $id);

                if(empty($user['errors'])) {
                    return $user->load('companies', 'user_type', 'user_template', 'user_extensions');
                } else {
                    return $user;
                }
            } else {
                return response()->json(['error' => 'unauthenticated'], 401);    
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Eliminamos el tipo de usuario identificada por el campo Id
     * @param Request $request
     * @param int $id Identificador del tipo de usuario
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function api_delete(Request $request, int $id) {
        $this->module = get_user_module_security($this->module_key);
        
        if(!empty($this->module->delete)) {
            if(self::validate_security($id)) {
                return self::delete($id);
            } else {
                return response()->json(['error' => 'unauthenticated'], 401);
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Valida y genera un nuevo registro del tipo de usuario con los datos facilitados en $data
     * @param array $data Campos a introducir
     * @return array Devuelve el objeto generado o un array con los errores de validación
     */
    private function create (Array $data) {
        //RC: Obtenemos el usuario logeado
        $loged_user = get_loged_user();

        //RC: Obtenemos las validaciones
        $validations = get_user_template_fields_validations($this->module->user_template_id, $this->module->module_id);

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.
            return ['errors' => $validator->errors()];
        } else {
            //RC: si la validación fue correcta tenemos que generar el objeto
            $data['password'] = bcrypt($data['password']);
            $data['company_id'] = $loged_user->company_id;
            $user = User::create($data);

            //RC: Tenemos que asignarlo a las empresas
            if(!empty($data['companies_id'])) {
                foreach($data['companies_id'] as $company_id) {
                    $user->companies()->attach($company_id);
                }
            }

            //RC: Miramos si la empresa seleccionada está incluida en las empresas seleccionadas.
            if($user->companies()->where('id', $user->company_id)->count() == 0) {
                $user->companies()->attach($user->company_id);
            }

            //RC: Si tenemos un departamento tenemos que añadirlo al departamento
            if(!empty($user->department_id)) {
                foreach($user->department->chat_rooms as $chat_room) {
                    $component['id'] = $user->id;
                    $component['type'] = 'user';
                    chat_room_add_component($chat_room, $chat_room->name, $component);
                }
            }

            //RC: Guardamos todas las extensiones disponibles
            if(!empty($data['extenions'])) {
                foreach($data['extenions'] as $extension) {
                    $user_extension = new UserExtension();
                    $user_extension->user_id = $user->id;
                    $user_extension->extension = $extension;
                    $user_extension->save();
                }
            }

            //RC: miramos si tenemos que guardar la tabla de campos personalizados
            $company_config = CompanyConfig::where('company_id', $loged_user->company_id)
                ->where('key', 'UserCustomFields')
                ->first();

            if (!empty($company_config)) {
                $fields = explode(',', $company_config->value);

                $user_custom = new UserCustom();
                $user_custom->user_id = $user->id;

                foreach ($fields as $field) {
                    if (isset($data[$field])) {
                        $user_custom->{$field} = $data[$field];
                    }
                }
                $user_custom->save();
            }

            return $user;
        }
    }

    /**
     * @author Roger Corominas
     * Valida y actualiza el registro del tipo de usuario indentificado por $id con los datos del Array $data
     * @param array $data Campos a modificar
     * @param int $id Identificador del tipo de usuario
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
            $user = User::findOrFail($id);
            if(!empty($data['password'])) {
                $data['password'] = bcrypt($data['password']);
            } else {
                $data['password'] = $user->password;
            }

            if(!empty($user->department_id)) {
                $old_department_id = $user->department_id;
            }
            $old_name = $user->name;

            $user->update($data);

            //RC: Tenemos que asignarlo a las empresas
            $user->companies()->detach();
            if(!empty($data['companies_id'])) {
                foreach($data['companies_id'] as $company_id) {
                    $user->companies()->attach($company_id);
                }
            }

            //RC: Miramos si la empresa seleccionada está incluida en las empresas seleccionadas.
            if($user->companies()->where('id', $user->company_id)->count() == 0) {
                $user->companies()->attach($user->company_id);
            }

            if(!empty($old_department_id) && $old_department_id != $user->department_id) {
                //RC: Tenemos que eliminar la asignación del departamento anterior
                $old_department = Department::findOrFail($old_department_id);

                $component['id'] = $user->id;
                $component['type'] = 'user';
                foreach($old_department->chat_rooms as $chat_room) {
                    chat_room_remove_component($chat_room, $component);
                }

                if(!empty($user->department_id)) {
                    //RC: tenemos que asignar el usuario al nuevo departamento
                    foreach ($user->department->chat_rooms as $chat_room) {
                        chat_room_add_component($chat_room, $chat_room->name, $component);
                    }
                }
            } else if(empty($old_department_id) && !empty($user->department_id)) {
                $component['id'] = $user->id;
                $component['type'] = 'user';
                foreach($user->department->chat_rooms as $chat_room) {
                    chat_room_add_component($chat_room, $chat_room->name, $component);
                }
            }

            if($old_name != $user->name) {
                DB::update('UPDATE user_chat_room SET name = "'.$user->name.'" WHERE name = "'.$old_name.'"');
            }

            //RC: Guardamos todas las extensiones disponibles
            if(!empty($data['extensions'])) {
                foreach($data['extensions'] as $extension) {
                    if($user->user_extensions()->where('extension', $extension)->count() == 0) {
                        $user_extension = new UserExtension();
                        $user_extension->user_id = $user->id;
                        $user_extension->extension = $extension;
                        $user_extension->save();
                    }
                }

                $user->user_extensions()->whereNotIn('extension', $data['extensions'])->delete();
            } else {
                $user->user_extensions()->delete();
            }

            //RC: miramos si tenemos que guardar la tabla de campos personalizados
            $company_config = CompanyConfig::where('company_id', $user->company_id)
                ->where('key', 'UserCustomFields')
                ->first();

            if (!empty($company_config)) {

                $fields = explode(',', $company_config->value);
                $user_custom = UserCustom::where('user_id', $user->id)->first();

                if (empty($user_custom)) {
                    $user_custom = new UserCustom();
                    $user_custom->user_id = $user->id;
                }

                foreach ($fields as $field) {
                    if (isset($data[$field])) {
                        $user_custom->{$field} = $data[$field];
                    } else {
                        $user_custom->{$field} = '';
                    }
                }
                $user_custom->save();
            }

            return $user;
        }
    }

    /**
     * @author Roger Corominas
     * Elimina el objeto
     * @param int $id Identificador del tipo de empresa
     * @return User
     */
    private function delete (int $id) {
        $user = User::findOrFail($id);

        $component['id'] = $user->id;
        $component['type'] = 'user';
        foreach($user->chat_rooms as $chat_room) {
            chat_room_remove_component($chat_room, $component);
        }

        $user->delete();
        return $user;
    }

    /**
     * @param integer $id Identificador del usuario
     * Devuelve si tenemos permiso para acceder a este elemento
     * @return bool 
     */
    private function validate_security(int $id) {
        $loged_user = get_loged_user();
        $user = User::findOrFail($id);
        
        if($user->companies()->where('id', $loged_user->company_id)->count() > 0) {
            return true;
        } else {
            return false;
        }
    }

    private function updateActiveSessionExtension($extension)
    {
        $user = get_loged_user();

        if (empty($user->active_session->extension)) {
            //RC: Si no tenemos extensión la seteamos
            $active_session = $user->active_session;
            $active_session->extension = $extension;
            $active_session->save();
        } else {
            //RC Si tenemos extensión tenemos que finalizar la sesion actual
            end_user_session($user->id, "", "");

            //RC: Generamos un nuevo registro con la nueva extensión
            start_user_session($user, "", "", $extension);
        }
    }
}
