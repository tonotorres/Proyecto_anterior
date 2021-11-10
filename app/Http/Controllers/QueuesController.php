<?php

namespace App\Http\Controllers;

use App\Queue;
use App\User;
use Illuminate\Http\Request;

class QueuesController extends Controller
{
    /**
     * @var int clave del módulo
     */
    private $module_key = 34;

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del cuenta indicado
     */
    private $module;

    public function api_get_queue_members($number)
    {
        return self::get_queue_members($number);
    }

    private function get_queue_members($number) {
        //RC: Obtenemos el usuario conectado
        $loged_user = get_loged_user();

        //RC: Obtenemos la cola
        $queue = Queue::where('number', $number)
            ->where('company_id', $loged_user->company_id)
            ->first();

        if(!empty($queue)) {
            //RC: Obtenemos los miembros
            $members = get_queue_users($number);

            foreach($members as $index => $member) {
                //RC: Obtenemos el usuario asociado a la exten
                $user = User::where('extension', $member->extension)
                    ->where('company_id', $loged_user->company_id)
                    ->first();

                if(!empty($user)) {
                    $members[$index]->disabled = $user->paused_queues()->where('id', $queue->id)->count();
                }
            }
        } else{
            abort(404);
        }

        return $members;
    }

    public function api_pause_queue_member($queueNumber, $extensionNumber) {
        //RC: Obtenemos el usuario conectado
        $loged_user = get_loged_user();

        //RC: Obtenemos la cola
        $queue = Queue::where('number', $queueNumber)
            ->where('company_id', $loged_user->company_id)
            ->first();

        if(!empty($queue)) {
            $res = pause_queue($queueNumber, $extensionNumber);

            $members = self::get_queue_members($queueNumber);

            return $members;
        } else {
            abort(404);
        }
    }

    public function api_unpause_queue_member($queueNumber, $extensionNumber) {
        //RC: Obtenemos el usuario conectado
        $loged_user = get_loged_user();

        //RC: Obtenemos la cola
        $queue = Queue::where('number', $queueNumber)
            ->where('company_id', $loged_user->company_id)
            ->first();

        if(!empty($queue)) {
            $res = unpause_queue($queueNumber, $extensionNumber);

            $members = self::get_queue_members($queueNumber);

            return $members;
        } else {
            abort(404);
        }
    }
    public function api_pause_all_queue_member($queueNumber, $extensionNumber) {
        //RC: Obtenemos el usuario conectado
        $loged_user = get_loged_user();

        $res = pause_all_extension($extensionNumber);

        $members = self::get_queue_members($queueNumber);

        return $members;
    }

    public function api_unpause_all_queue_member($queueNumber, $extensionNumber) {
        //RC: Obtenemos el usuario conectado
        $loged_user = get_loged_user();

        $res = unpause_all_extension($extensionNumber);

        $members = self::get_queue_members($queueNumber);

        return $members;
    }

    public function api_get_my_queues() {
        $user = get_loged_user();
        $queues = [];
        
        if(!empty($user->extension)) {
            $freepbx_queues = get_user_queues($user->extension);
            $i = 0;
            if(!empty($freepbx_queues)) {
                foreach($freepbx_queues as $freepbx_queue) {
                    $queue = Queue::where('number', $freepbx_queue->number)
                        ->where('company_id', $user->company_id)
                        ->first();

                    //RC: Si no tenemos la cola en el sistema la tenemos que generar
                    if (empty($queue)) {
                        $queue = new Queue();
                        $queue->company_id = $user->company_id;
                        $queue->name = $freepbx_queue->number;
                        $queue->number = $freepbx_queue->number;
                        $queue->save();
                    }

                    $queues[$i]['id'] = $queue->id;
                    $queues[$i]['number'] = $queue->number;
                    $queues[$i]['name'] = $queue->name;
                    $queues[$i]['paused'] = $freepbx_queue->paused;
                    $i++;
                }
            }
        }

        return $queues;
    }

    public function api_pause_queue($id) {
        $user = get_loged_user();
        $queue = Queue::findOrFail($id);

        if($user->extension) {
            $queue->paused_users()->attach($user->id);
            pause_queue($queue->number, $user->extension);    

            return ['error' => false];
        } else {
            return ['error' => true];
        }

    }

    public function api_unpause_queue($id) {
        $user = get_loged_user();
        $queue = Queue::findOrFail($id);

        if($user->extension) {
            $queue->paused_users()->detach($user->id);
            unpause_queue($queue->number, $user->extension);    

            return ['error' => false];
        } else {
            return ['error' => true];
        }

    }

    /**
     * @author Roger Corominas
     * Devuelve un array con todas las cuentas activos
     * @return Queue[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all() {
        $this->module = get_user_module_security($this->module_key);
        $queues = self::get_all();
        if (!empty($queues)) {
            return $queues;
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_get_list()
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->read)) {
            $user = get_loged_user();
            return Queue::select('id', 'name as label')
                ->where('company_id', $user->company_id)
                ->get();
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

        $queue = self::get($id);
        if (!empty($queue)) {
            return $queue;
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
            $queue = self::create($request->all());

            if(empty($queue['errors'])) {
                return $queue->load('department');
            } else {
                return $queue;
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
            $queue = self::update($request->all(), $id);

            if(empty($queue['errors'])) {
                return $queue->load('department');
            } else {
                return $queue;
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

    private function get_all() {
        //RC: Si no tenemos la seguridad del módulo lo volvemos a generar
        if (empty($this->module)) {
            $this->module = get_user_module_security($this->module_key);
        }

        if(!empty($this->module->read)) {
            $user = get_loged_user();
            return Queue::where('company_id', $user->company_id)->get()->load('department');
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
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
            $queue = Queue::findOrFail($id);
            if($user->company_id == $queue->company_id) {
                return $queue->load('department');
            } else {
                return null;
            }
        } else {
            return null;
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
            $queue = Queue::create($data);

            return $queue;
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
            $user = get_loged_user();
            $queue = Queue::findOrFail($id);
            
            if($user->company_id == $queue->company_id) {
                $queue->update($data);
            }

            return $queue;
        }
    }

    /**
     * @author Roger Corominas
     * Elimina el objeto
     * @param int $id Identificador de la cuenta
     * @return Queue
     */
    private function delete (int $id) {
        $user = get_loged_user();
        $queue = Queue::findOrFail($id);
        
        if($user->company_id == $queue->company_id) {
            $queue->delete();
        }

        return $queue;
    }
}
