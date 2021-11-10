<?php

namespace App\Http\Controllers;

use App\Module;
use App\UserTemplate;
use App\UserTemplateField;
use App\UserTemplateModule;
use App\UserTemplateSection;
use App\UserTemplateTab;
use Illuminate\Http\Request;

class UserTemplatesController extends Controller
{
    /**
     * @var int clave del módulo
     */
    private $module_key = 2;

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del usuario indicado
     */
    private $module;


    public function get_my_user_template() {
        $user_template_id = 1;
        $user_template = UserTemplate::findOrFail($user_template_id);
        $security = [];

        foreach($user_template->user_template_modules as $user_template_module) {
            $security[$user_template_module->module->key]['create'] = $user_template_module->create;
            $security[$user_template_module->module->key]['read'] = $user_template_module->read;
            $security[$user_template_module->module->key]['update'] = $user_template_module->update;
            $security[$user_template_module->module->key]['delete'] = $user_template_module->delete;
            $security[$user_template_module->module->key]['list'] = $user_template_module->list;
            $security[$user_template_module->module->key]['own'] = $user_template_module->own;
            $security[$user_template_module->module->key]['user_template_tabs'] = $user_template_module->user_template_tabs()->orderBy('position', 'ASC')->get()->load(
                'user_template_sections',
                'user_template_sections.user_template_fields',
                'user_template_sections.user_template_fields.field_type'
            );

            //RC: Cargamos la información para los posibles cruds genéricos
            $security[$user_template_module->module->key]['name'] = $user_template_module->name;
            $security[$user_template_module->module->key]['url'] = $user_template_module->module->url;
            if ($user_template_module->module->module_table_rows()->count() > 0) {
                $security[$user_template_module->module->key]['table_rows'] = $user_template_module->module->module_table_rows;
            } else {
                $security[$user_template_module->module->key]['table_rows'] = [];
            }
        }

        return $security;
    }

    /**
     * @author Roger Corominas
     * Devuelve un array con todas las cuentas activos
     * @return UserTemplate[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all()
    {
        $this->module = get_user_module_security($this->module_key);
        $user_templates = self::get_all();
        if (!empty($user_templates)) {
            return $user_templates;
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_get_list()
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->list)) {
            $user = get_loged_user();
            return UserTemplate::select('id', 'name as label')
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
    public function api_get(int $id)
    {
        $this->module = get_user_module_security($this->module_key);

        $user_template = self::get($id);
        if (!empty($user_template)) {
            return $user_template;
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
            $user_template = self::create($request->all());

            if (empty($user_template['errors'])) {
                return $user_template->load('user_template_modules');
            } else {
                return $user_template;
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
            $user_template = self::update($request->all(), $id);

            if (empty($user_template['errors'])) {
                return $user_template->load('user_template_modules');
            } else {
                return $user_template;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_partial_update(Request $request, int $id)
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->update)) {
            $user_template = self::partial_update($request->all(), $id);

            if (empty($user_template['errors'])) {
                return $user_template->load('user_template_modules');
            } else {
                return $user_template;
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
            return UserTemplate::where('company_id', $user->company_id)->get()->load('user_template_modules');
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
            $user_template = UserTemplate::findOrFail($id);

            if ($user_template->company_id != $user->company_id) {
                return null;
            } else {
                //RC: Si es de la misma compañía lo podemos devolver, en caso contrario no lo 
                return $user_template->load('user_template_modules');
            }
        } else {
            return null;
        }

        if (!empty($this->module->read)) {
            return UserTemplate::findOrFail($id);
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
            $data['company_id'] = $user->company_id;
            $user_template = UserTemplate::create($data);

            $modules = Module::all();

            foreach ($modules as $module) {
                //RC: Para cada módulo miramos si existe i en caso contrario lo creamos
                if ($user_template->user_template_modules()->where('module_id', $module->id)->count() == 0) {
                    //RC: Solo queremos dar permisos crud para el superadministrador
                    $crud = false;


                    $user_template_module = new UserTemplateModule();
                    $user_template_module->user_template_id = $user_template->id;
                    $user_template_module->module_id = $module->id;
                    $user_template_module->name = $module->name;
                    $user_template_module->create = $crud;
                    $user_template_module->read = $crud;
                    $user_template_module->update = $crud;
                    $user_template_module->delete = $crud;
                    $user_template_module->list = $crud;
                    $user_template_module->own = $crud;
                    $user_template_module->save();
                } else {
                    //RC: Obtenemos el módulo si ya está generado
                    $user_template_module = $user_template->user_template_modules()->where('module_id', $module->id)->first();
                }

                //RC: Miramos pestaña por pestaña para ver que esté todo incluido
                foreach ($module->tabs as $tab) {
                    if ($user_template_module->user_template_tabs()->where('tab_id', $tab->id)->count() == 0) {
                        $user_template_tab = new UserTemplateTab();
                        $user_template_tab->user_template_module_id = $user_template_module->id;
                        $user_template_tab->tab_id = $tab->id;
                        $user_template_tab->name = $tab->name;
                        $user_template_tab->position = $tab->position;
                        $user_template_tab->save();
                    } else {
                        $user_template_tab = $user_template_module->user_template_tabs()->where('tab_id', $tab->id)->first();
                    }

                    foreach ($tab->sections as $section) {
                        if ($user_template_tab->user_template_sections()->where('section_id', $section->id)->count() == 0) {
                            $user_template_section = new UserTemplateSection();
                            $user_template_section->user_template_tab_id = $user_template_tab->id;
                            $user_template_section->section_id = $section->id;
                            $user_template_section->name = $section->name;
                            $user_template_section->position = $section->position;
                            $user_template_section->save();
                        } else {
                            $user_template_section = $user_template_tab->user_template_sections()->where('section_id', $section->id)->first();
                        }

                        foreach ($section->fields as $field) {
                            if ($user_template_section->user_template_fields()->where('field_id', $field->id)->count() == 0) {
                                $user_template_field = new UserTemplateField();
                                $user_template_field->user_template_section_id = $user_template_section->id;
                                $user_template_field->field_id = $field->id;
                                $user_template_field->field_type_id = $field->field_type_id;
                                $user_template_field->key = $field->key;
                                $user_template_field->width = $field->width;
                                $user_template_field->label = $field->label;
                                $user_template_field->name = $field->name;
                                $user_template_field->default = $field->default;
                                $user_template_field->validations_create = $field->validations_create;
                                $user_template_field->validations_update = $field->validations_update;
                                $user_template_field->options = $field->options;
                                $user_template_field->position = $field->position;
                                $user_template_field->is_simple_form = $field->is_simple_form;
                                $user_template_field->save();
                            }
                        }
                    }
                }
            }

            return $user_template;
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
            $user_template = UserTemplate::findOrFail($id);
            $user = get_loged_user();
            if ($user->company_id == $user_template->company_id) {
                $user_template->update($data);
            }

            return $user_template;
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
            $user_template = UserTemplate::findOrFail($id);
            $user = get_loged_user();
            if ($user->company_id == $user_template->company_id) {
                $user_template->update($data);
            }

            return $user_template;
        }
    }

    /**
     * @author Roger Corominas
     * Elimina el objeto
     * @param int $id Identificador de la cuenta
     * @return UserTemplate
     */
    private function delete(int $id)
    {
        $user_template = UserTemplate::findOrFail($id);
        $user = get_loged_user();
        if ($user->company_id == $user_template->company_id) {
            $user_template->delete();
        }

        return $user_template;
    }
}
