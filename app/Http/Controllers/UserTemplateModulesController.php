<?php

namespace App\Http\Controllers;

use App\UserTemplateModule;
use Illuminate\Http\Request;

class UserTemplateModulesController extends Controller
{
    private $module_key = 53;

    private $module;

    /**
     * @author Roger Corominas
     * Devuelve un array con todas las cuentas activos
     * @return UserTemplateModule[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all()
    {
        $this->module = get_user_module_security($this->module_key);
        $user_template_modules = self::get_all();
        if (!empty($user_template_modules)) {
            return $user_template_modules;
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_get_list()
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->list)) {
            $user = get_loged_user();
            return UserTemplateModule::select('id', 'name as label')
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
    public function api_get(int $id)
    {
        $this->module = get_user_module_security($this->module_key);

        $user_template_module = self::get($id);
        if (!empty($user_template_module)) {
            return $user_template_module;
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
    public function api_store(Request $request)
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->create)) {
            $user_template_module = self::create($request->all());

            if (empty($user_template_module['errors'])) {
                return $user_template_module->load('user_template', 'module');
            } else {
                return $user_template_module;
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
    public function api_update(Request $request, int $id)
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->update)) {
            $user_template_module = self::update($request->all(), $id);

            if (empty($user_template_module['errors'])) {
                return $user_template_module->load('user_template', 'module');
            } else {
                return $user_template_module;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_partial_update(Request $request, int $id)
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->update)) {
            $user_template_module = self::partial_update($request->all(), $id);

            if (empty($user_template_module['errors'])) {
                return $user_template_module->load('user_template', 'module');
            } else {
                return $user_template_module;
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
    public function api_delete(Request $request, int $id)
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->delete)) {
            return self::delete($id);
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    private function get_all()
    {
        //RC: Si no tenemos la seguridad del módulo lo volvemos a generar
        if (empty($this->module)) {
            $this->module = get_user_module_security($this->module_key);
        }

        if (!empty($this->module->read)) {
            $user = get_loged_user();
            return UserTemplateModule::get()->load('user_template', 'module');
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    private function get($id)
    {
        //RC: Si no tenemos la seguridad del módulo lo volvemos a generar
        if (empty($this->module)) {
            $this->module = get_user_module_security($this->module_key);
        }

        //RC: Miramos si tenemos permisos para leer el objecto
        if (!empty($this->module->read)) {
            $user = get_loged_user();
            $user_template_module = UserTemplateModule::findOrFail($id);

            if ($user_template_module->company_id != $user->company_id) {
                return null;
            } else {
                //RC: Si es de la misma compañía lo podemos devolver, en caso contrario no lo 
                return $user_template_module->load('user_template', 'module');
            }
        } else {
            return null;
        }

        if (!empty($this->module->read)) {
            return UserTemplateModule::findOrFail($id);
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
    private function create(array $data)
    {
        //RC: Obtenemos las validaciones
        $validations = get_user_template_fields_validations($this->module->user_template_id, $this->module->module_id);

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.
            return ['errors' => $validator->errors()];
        } else {
            //RC: si la validación fue correcta tenemos que generar el objeto
            $user = get_loged_user();
            $user_template_module = UserTemplateModule::create($data);

            return $user_template_module;
        }
    }

    /**
     * @author Roger Corominas
     * Valida y actualiza el registro de la cuenta indentificado por $id con los datos del Array $data
     * @param array $data Campos a modificar
     * @param int $id Identificador de la cuenta
     * @return array Devuelve el objeto actualizado o un array con los errores de validación.
     */
    private function update(array $data, int $id)
    {
        //RC: Obtenemos las validaciones
        $validations = get_user_template_fields_validations($this->module->user_template_id, $this->module->module_id, $id);

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.
            return ['errors' => $validator->errors()];
        } else {
            //RC: si la validación fue correcta tenemos que generar el objeto
            $user_template_module = UserTemplateModule::findOrFail($id);
            $user = get_loged_user();
            $user_template_module->update($data);


            return $user_template_module;
        }
    }

    private function partial_update(array $data, int $id)
    {
        //RC: Obtenemos las validaciones
        $validations = get_user_template_fields_partial_validations($this->module->user_template_id, $this->module->module_id, $data, $id);

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.
            return ['errors' => $validator->errors()];
        } else {
            //RC: si la validación fue correcta tenemos que generar el objeto
            $user_template_module = UserTemplateModule::findOrFail($id);
            $user = get_loged_user();
            if ($user->company_id == $user_template_module->company_id) {
                $user_template_module->update($data);
            }

            return $user_template_module;
        }
    }

    /**
     * @author Roger Corominas
     * Elimina el objeto
     * @param int $id Identificador de la cuenta
     * @return UserTemplateModule
     */
    private function delete(int $id)
    {
        $user_template_module = UserTemplateModule::findOrFail($id);
        $user = get_loged_user();
        if ($user->company_id == $user_template_module->company_id) {
            $user_template_module->delete();
        }

        return $user_template_module;
    }

    /*public function api_get(Request $request, $user_template_module_id, $module_id) {
        $user_template_module_id = 1;
        return  UserTemplateModuleModule::where('user_template_module_id', $user_template_module_id)
            ->where('module_id', $module_id)
            ->first()
            ->load(
                'user_template_module_tabs',
                'user_template_module_tabs.user_template_module_sections',
                'user_template_module_tabs.user_template_module_sections.user_template_module_fields',
                'user_template_module_tabs.user_template_module_sections.user_template_module_fields.field_type'
            );
    }*/
}
