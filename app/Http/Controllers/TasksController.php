<?php

namespace App\Http\Controllers;

use App\CompanyConfig;
use App\Events\TaskCreate;
use App\Events\TaskDelete;
use App\Events\TaskEnd;
use App\Events\TaskStart;
use App\Events\TaskUpdate;
use App\Task;
use App\TaskDescription;
use App\TaskStatus;
use App\TaskTime;
use App\TaskTimeDescription;
use Illuminate\Http\Request;

class TasksController extends Controller
{
    /**
     * @var int clave del módulo
     */
    private $module_key = 51;
    private $relate_properties = ['task_list', 'task_type', 'parent_task', 'children_tasks', 'task_priority', 'task_status', 'task_description', 'task_comments', 'task_comments.user', 'task_times', 'task_times.user', 'task_times.task_time_description', 'users'];

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del cuenta indicado
     */
    private $module;

    public function api_get_my_tasks($finish = 0)
    {
        $this->module = get_user_module_security($this->module_key);
        $tasks = self::get_my_tasks($finish);
        if (!empty($tasks)) {
            return $tasks;
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Devuelve un array con todas las cuentas activos
     * @return Task[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_active($task_list_id = null) {
        $this->module = get_user_module_security($this->module_key);
        $tasks = self::get_all($task_list_id);
        if (!empty($tasks)) {
            return $tasks;
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_get_finished($task_list_id = null) {
        $this->module = get_user_module_security($this->module_key);
        $tasks = self::get_all($task_list_id, true);
        if (!empty($tasks)) {
            return $tasks;
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

        $task = self::get($id);
        if (!empty($task)) {
            return $task;
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
            $task = self::create($request->all());

            if(empty($task['errors'])) {
                return $task->load($this->relate_properties);
            } else {
                return $task;
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
            $task = self::update($request->all(), $id);

            if(empty($task['errors'])) {
                return $task->load($this->relate_properties);
            } else {
                return $task;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_partial_update(Request $request, int $id) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->update)) {
            $task = self::partial_update($request->all(), $id);

            if(empty($task['errors'])) {
                return $task->load($this->relate_properties);
            } else {
                return $task;
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

    public function api_start_time($id) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->read) || !empty($this->module->own)) {
            return self::start_time($id);
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_end_time(Request $request, $id) {
        $this->module = get_user_module_security($this->module_key);
        if(!empty($this->module->read) || !empty($this->module->own)) {
            if(!empty($request->description)) {
                $description = $request->description;
            } else {
                $description = '';
            }

            return self::end_time($id, $description);
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_finish(Request $request, $id) {
        $this->module = get_user_module_security($this->module_key);
        if(!empty($this->module->read) || !empty($this->module->own)) {
            $data['finish'] = strtotime('now');
            $task = self::partial_update($data, $id);

            if(empty($task['errors'])) {
                return $task->load($this->relate_properties);
            } else {
                return $task;
            }

        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    private function start_time($id) {
        $user = get_loged_user();
        $task = Task::findOrFail($id);

        if($task->company_id == $user->company_id) {
            self::end_time($id);

            $task_time = new TaskTime();
            $task_time->task_id = $task->id;
            $task_time->user_id = $user->id;
            $task_time->start = strtotime('now');
            $task_time->save();

            broadcast(new TaskStart($task->load($this->relate_properties)));

            return $task->load($this->relate_properties);
        } else {
            return null;
        }
    }

    private function end_time($id, $description = '') {
        $user = get_loged_user();
        $task = Task::findOrFail($id);

        if($task->company_id == $user->company_id) {

            $task_times = TaskTime::where('task_id', $id)
                ->where('user_id', $user->id)
                ->where('duration', '0')
                ->get();

            $total_duration = 0;
            foreach($task_times as $task_time) {
                $now = strtotime('now');
                $task_time->duration = $now - $task_time->start;
                if($task_time->duration == 0) {
                    $task_time->duration = 1;
                }

                $task_time->save();

                if(!empty($description)) {
                    $task_time_description = new TaskTimeDescription();
                    $task_time_description->task_time_id = $task_time->id;
                    $task_time_description->description = $description;
                    $task_time_description->save();
                }

                $total_duration += $task_time->duration;
            }

            $task->duration += $total_duration;
            $task->save();

            broadcast(new TaskEnd($task->load($this->relate_properties)));

            return $task->load($this->relate_properties);
        } else {
            return null;
        }
    }

    private function get_my_tasks($finished = 0)
    {
        //RC: Si no tenemos la seguridad del módulo lo volvemos a generar
        if (empty($this->module)) {
            $this->module = get_user_module_security($this->module_key);
        }

        $user = get_loged_user();

        $task = Task::getUserTasks($user->company_id, $user->id);

        if (!$finished) {
            $task->where('finish', 0);
        } else {
            $task->where('finish', 1);
        }

        return $task->get()
            ->load($this->relate_properties);
    }

    private function get_all($task_list_id, $finished = false) {
        //RC: Si no tenemos la seguridad del módulo lo volvemos a generar
        if (empty($this->module)) {
            $this->module = get_user_module_security($this->module_key);
        }

        $user = get_loged_user();
        if(!empty($this->module->read)) {
            $task = Task::where('company_id', $user->company_id);

            if(!$finished) {
                $task->where('finish', 0);
            } else {
                $task->where('finish', '>', 0);
            }

            if(!empty($task_list_id)) {
                $task->where('task_list_id', $task_list_id);
            }
            
            return $task->get()
                ->load($this->relate_properties);
        } else if(!empty($this->module->own)) {
            $task = Task::getUserTasks($user->company_id, $user->id);

            if(!$finished) {
                $task->where('finish', 0);
            }

            if(!empty($task_list_id)) {
                $task->where('task_list_id', $task_list_id);
            }

            return $task->get()
                ->load($this->relate_properties);
        }
        else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    private function get($id) {
        //RC: Si no tenemos la seguridad del módulo lo volvemos a generar
        if (empty($this->module)) {
            $this->module = get_user_module_security($this->module_key);
        }

        //RC: Miramos si tenemos permisos para leer el objecto
        if (!empty($this->module->read) || !empty($this->module->own)) {
            $user = get_loged_user();
            $task = Task::findOrFail($id);

            if($task->company_id != $user->company_id) {
                return null;
            } else {
                if(!empty($this->module->read)) {
                //RC: Si es de la misma compañía lo podemos devolver, en caso contrario no lo 
                    return $task->load($this->relate_properties);
                } else {
                    if($task->users()->where('id', $user->id)->count() > 0) {
                        return $task->load($this->relate_properties);
                    } else {
                        return null;
                    }
                }
            }
        } else {
            return null;
        }

        if(!empty($this->module->read)) {
            return Task::findOrFail($id);
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

        $data = self::validate_default_values($data);

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.
            return ['errors' => $validator->errors()];
        } else {
            //RC: si la validación fue correcta tenemos que generar el objeto
            $user = get_loged_user();
            $data['company_id'] = $user->company_id;
            
            $task = Task::create($data);

            $task_description = new TaskDescription();
            $task_description->task_id = $task->id;
            if(empty($data['description'])) {
                $data['description'] = '';
            }
            $task_description->description = $data['description'];
            $task_description->save();

            if(empty($data['users_id'])) {
                $data['users_id'][] = $user->id;
            }

            $task->users()->sync($data['users_id']);

            broadcast(new TaskCreate($task->load($this->relate_properties)));

            return $task;
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

        $data = self::validate_default_values($data);

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.s
            return ['errors' => $validator->errors()];
        } else {
            //RC: si la validación fue correcta tenemos que generar el objeto
            $task = Task::findOrFail($id);
            $user = get_loged_user();
            if($user->company_id == $task->company_id) {
                if ($data['task_status_id'] != $task->task_status_id) {
                    $task_status = TaskStatus::where('id', $data['task_status_id'])->first();

                    if (!empty($task_status) && $task_status->finish) {
                        $data['finish'] = 1;
                    } else {
                        $data['finish'] = 0;
                    }
                }

                $task->update($data);

                if(empty($data['description'])) {
                    $data['description'] = '';
                }

                $task->task_description->description = $data['description'];
                $task->task_description->save();

                if(empty($data['users_id'])) {
                    $data['users_id'][] = $user->id;
                }
    
                $task->users()->sync($data['users_id']);
            }

            broadcast(new TaskUpdate($task->load($this->relate_properties)));

            return $task;
        }
    }

    private function partial_update (Array $data, int $id) {
        //RC: Obtenemos las validaciones
        $validations = get_user_template_fields_partial_validations($this->module->user_template_id, $this->module->module_id, $data, $id);

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.
            return ['errors' => $validator->errors()];
        } else {
            //RC: si la validación fue correcta tenemos que generar el objeto
            $task = Task::findOrFail($id);
            $user = get_loged_user();
            if($user->company_id == $task->company_id) {

                //RC: seteamos la fecha de inicio
                if(!empty($data['start'])) {
                    $data['start'] = strtotime($data['start']);

                    if ($data['start'] == 0) {
                        $data['start'] = null;
                    }
                }

                if (!empty($data['task_status_id']) && $data['task_status_id'] != $task->task_status_id) {
                    $task_status = TaskStatus::where('id', $data['task_status_id'])->first();

                    if (!empty($task_status) && $task_status->finish) {
                        $data['finish'] = 1;
                    } else {
                        $data['finish'] = 0;
                    }
                }

                $task->update($data);

                if(!empty($data['description'])) {
                    $task->task_description->description = $data['description'];
                    $task->task_description->save();
                }
    
                if(!empty($data['users_id'])) {
                    $task->users()->sync($data['users_id']);
                }
            }

            broadcast(new TaskUpdate($task->load($this->relate_properties)));

            return $task;
        }
    }

    private function validate_default_values($data) {
        $user = get_loged_user();

        //RC: Miramos los datos por defecto
        if(empty($data['task_type_id'])) {
            $config = CompanyConfig::where(
                'company_id',
                $user->company_id
            )->where('key', 'default_task_type')->first();
            if (!empty($config)) {
                $data['task_type_id'] = $config->value;
            }
        }

        if(empty($data['task_priority_id'])) {
            $config = CompanyConfig::where(
                'company_id',
                $user->company_id
            )->where('key', 'default_task_priority')->first();
            if (!empty($config)) {
                $data['task_priority_id'] = $config->value;
            }
        }

        if(empty($data['task_status_id'])) {
            $config = CompanyConfig::where(
                'company_id',
                $user->company_id
            )->where('key', 'default_task_status')->first();
            if (!empty($config)) {
                $data['task_status_id'] = $config->value;
            }
        }

        if (!empty($data['start']) && !is_numeric($data['start'])) {
            $data['start'] = strtotime($data['start']);
        }

        if (empty($data['start'])) {
            $data['start'] = null;
        }

        if(!empty($data['end']) && !is_numeric($data['end'])) {
            $data['end'] = strtotime($data['end']);
        }

        return $data;
    }

    /**
     * @author Roger Corominas
     * Elimina el objeto
     * @param int $id Identificador de la cuenta
     * @return Task
     */
    private function delete (int $id) {
        $task = Task::findOrFail($id);
        $user = get_loged_user();
        if($user->company_id == $task->company_id) {
            $task->delete();

            broadcast(new TaskDelete($task->load($this->relate_properties)));
        }

        return $task;
    }
}
