<?php

namespace App\Http\Controllers;

use App\Events\ExtensionStatus as EventsExtensionStatus;
use App\Extension;
use App\ExtensionStatusLog;
use Illuminate\Http\Request;

class ExtensionsController extends Controller
{
    /**
     * @var int clave del módulo
     */
    private $module_key = 31;

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del cuenta indicado
     */
    private $module;

    /**
     * @author Roger Corominas
     * Devuelve un array con todas las cuentas activos
     * @return Extension[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all() {
        $this->module = get_user_module_security($this->module_key);
        $extensions = self::get_all();
        if (!empty($extensions)) {
            return $extensions->load('department');
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_get_list()
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->read)) {
            $user = get_loged_user();
            return Extension::select('id', 'name as label')
                ->where('company_id', $user->company_id)
                ->get();
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_get_list_number()
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->read)) {
            $user = get_loged_user();
            return Extension::selectRaw('number as id, CONCAT(number, " - ", name) as label')
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

        $extension = self::get($id);
        if (!empty($extension)) {
            return $extension;
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
            $extension = self::create($request->all());

            if(empty($extension['errors'])) {
                return $extension->load('department');
            } else {
                return $extension;
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
            $extension = self::update($request->all(), $id);

            if(empty($extension['errors'])) {
                return $extension->load('department');
            } else {
                return $extension;
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

    public function api_my_extension() {
        $user = get_loged_user();
        
        if(!empty($user->extension)) {
            return Extension::where('company_id', $user->company_id)
                ->where('number', $user->extension)
                ->first();
        } else {
            return [];
        }
    }

    public function api_get_all_status() {
        $user = get_loged_user();
        $extensions = Extension::where('company_id', $user->company_id)
            ->get()
            ->load('extension_status');

        return $extensions;
    }

    public function api_get_extension_status_log($extension_id) {
        $user = get_loged_user();
        $log = ExtensionStatusLog::where('extension_id', $extension_id)
            ->orderBy('id', 'desc')
            ->limit(100)
            ->get()
            ->load('extension_status');

        return $log;
    }

    public function api_status(Request $request) {
        if($request->company_id == -1) {
            $extension = Extension::where('number', $request->extension)
                ->first();
        } else {
            $extension = Extension::where('number', $request->extension)
                ->where('company_id', $request->company_id)
                ->first();
        }

        if(!empty($extension)) {
            switch($request->status_code) {
                case '-2':
                    $extension_status_id = 1;
                break;
                case '-1':
                    $extension_status_id = 2;
                break;
                case '0':
                    $extension_status_id = 3;
                break;
                case '1':
                    $extension_status_id = 4;
                break;
                case '2':
                    $extension_status_id = 5;
                break;
                case '4':
                    $extension_status_id = 6;
                break;
                case '8':
                    $extension_status_id = 7;
                break;
                case '9':
                    $extension_status_id = 8;
                break;
                case '16':
                    $extension_status_id = 9;
                break;
                case '17':
                    $extension_status_id = 10;
                break;
                default:
                    $extension_status_id = null;
                break;
            }

            $extension->extension_status_id = $extension_status_id;
            $extension->save();

            $extension_status_log = new ExtensionStatusLog();
            $extension_status_log->extension_id = $extension->id;
            $extension_status_log->extension_status_id = $extension_status_id;
            $extension_status_log->save();

            broadcast(new EventsExtensionStatus($extension->load('extension_status'), $extension->company_id));

            return $extension;
        } else {
            abort(404);
        }
        
        exit;
    }

    private function get_all() {
        //RC: Si no tenemos la seguridad del módulo lo volvemos a generar
        if (empty($this->module)) {
            $this->module = get_user_module_security($this->module_key);
        }

        if(!empty($this->module->read)) {
            $user = get_loged_user();
            return Extension::where('company_id', $user->company_id)->get()->load('department');
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
            $extension = Extension::findOrFail($id);
            if($user->company_id == $extension->company_id) {
                return $extension->load('department');
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
            $extension = Extension::create($data);

            return $extension;
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
            $extension = Extension::findOrFail($id);
            
            if($user->company_id == $extension->company_id) {
                $extension->update($data);
            }

            return $extension;
        }
    }

    /**
     * @author Roger Corominas
     * Elimina el objeto
     * @param int $id Identificador de la cuenta
     * @return Extension
     */
    private function delete (int $id) {
        $user = get_loged_user();
        $extension = Extension::findOrFail($id);
        
        if($user->company_id == $extension->company_id) {
            $extension->delete();
        }

        return $extension;
    }
}
