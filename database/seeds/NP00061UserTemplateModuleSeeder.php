<?php

use Illuminate\Database\Seeder;

class NP00061UserTemplateModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $module_key = 53;

        //RC: Generamos el registro del módulo
        if (\App\Module::where('key', $module_key)->count() == 0) {
            \App\Module::create([
                'key' => $module_key,
                'name' => 'Módulos de las plantillas de tarea',
                'url' => 'user_template_modules',
                'help' => 'help/user_template_modules'
            ]);
        }

        //RC: Generamos la estructura para generar el formulario
        $tabs = [
            [
                'name' => 'General',
                'sections' =>  [
                    [
                        'name' => 'Información general',
                        'fields' => [
                            [
                                'field_type_id' => 1,
                                'width' => 'is-4',
                                'label' => 'Plantilla de usuario',
                                'name' => 'user_template_id',
                                'default' => null,
                                'options' => 'user_templates:id,name',
                                'validations_create' => 'required|exists:user_templates,id',
                                'validations_update' => 'required|exists:user_templates,id',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 1,
                                'width' => 'is-4',
                                'label' => 'Modulos',
                                'name' => 'module_id',
                                'default' => null,
                                'options' => 'modules:id,name',
                                'validations_create' => 'required|exists:modules,id',
                                'validations_update' => 'required|exists:modules,id',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-4',
                                'label' => 'Nombre',
                                'name' => 'name',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required|string|max:50',
                                'validations_update' => 'required|string|max:50',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-12',
                                'label' => 'Creación',
                                'name' => 'create',
                                'default' => null,
                                'options' => "0:No\r\n1:Si",
                                'validations_create' => 'required',
                                'validations_update' => 'required',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-12',
                                'label' => 'Lectura',
                                'name' => 'read',
                                'default' => null,
                                'options' => "0:No\r\n1:Si",
                                'validations_create' => 'required',
                                'validations_update' => 'required',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-12',
                                'label' => 'Modificación',
                                'name' => 'update',
                                'default' => null,
                                'options' => "0:No\r\n1:Si",
                                'validations_create' => 'required',
                                'validations_update' => 'required',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-12',
                                'label' => 'Eliminación',
                                'name' => 'delete',
                                'default' => null,
                                'options' => "0:No\r\n1:Si",
                                'validations_create' => 'required',
                                'validations_update' => 'required',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-12',
                                'label' => 'Listados',
                                'name' => 'list',
                                'default' => null,
                                'options' => "0:No\r\n1:Si",
                                'validations_create' => 'required',
                                'validations_update' => 'required',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-12',
                                'label' => 'Propios',
                                'name' => 'own',
                                'default' => null,
                                'options' => "0:No\r\n1:Si",
                                'validations_create' => 'required',
                                'validations_update' => 'required',
                                'is_simple_form' => true
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $table_rows = [
            [
                'name' => 'user_template.name',
                'label' => 'Plantilla de usuario',
                'form' => 'user_template_id',
                'background' => null
            ],
            [
                'name' => 'module.name',
                'label' => 'Nombre del módulo',
                'form' => 'module_id',
                'background' => null
            ],
            [
                'name' => 'name',
                'label' => 'Nombre',
                'form' => 'name',
                'background' => null
            ],
            [
                'name' => 'read',
                'label' => 'Lectura',
                'form' => 'read',
                'background' => 'true'
            ],
            [
                'name' => 'own',
                'label' => 'Propios',
                'form' => 'own',
                'background' => 'true'
            ],
            [
                'name' => 'list',
                'label' => 'Listado',
                'form' => 'list',
                'background' => 'true'
            ],
            [
                'name' => 'create',
                'label' => 'Creación',
                'form' => 'create',
                'background' => 'true'
            ],
            [
                'name' => 'update',
                'label' => 'Modificación',
                'form' => 'update',
                'background' => 'true'
            ],
            [
                'name' => 'delete',
                'label' => 'Eliminación',
                'form' => 'delete',
                'background' => 'true'
            ]
        ];

        //RC: obtenemos el registro del módulo
        $module = \App\Module::where('key', $module_key)->first();

        //RC: generamos la tabla
        $i = 0;
        \App\ModuleTableRow::where('module_id', $module->id)->delete();
        foreach ($table_rows as $table_row) {
            $table_row['module_id'] = $module->id;
            $table_row['position'] = $i;
            \App\ModuleTableRow::create($table_row);

            $i = $i + 10;
        }



        \App\Tab::where('module_id', $module->id)->delete();

        //RC: Generamos la estructura de los formularios
        $i_tab = 0;
        foreach ($tabs as $tab) {
            $tab['module_id'] = $module->id;
            $tab['position'] = $i_tab;

            $sections = $tab['sections'];
            unset($tab['sections']);
            $tabReg = \App\Tab::create($tab);
            if (!empty($sections)) {
                $i_section = 0;

                foreach ($sections as $section) {
                    $section['tab_id'] = $tabReg->id;
                    $section['position'] = $i_section;

                    $fields = $section['fields'];
                    unset($section['fields']);
                    $sectionReg = \App\Section::create($section);
                    if (!empty($fields)) {
                        $i_field = 0;
                        foreach ($fields as $field) {
                            $field['section_id'] = $sectionReg->id;
                            $field['key'] = $module->key . '_' . $field['name'];
                            $field['position'] = $i_field;

                            \App\Field::create($field);
                            $i_field++;
                        }
                    }

                    $i_section++;
                }
            }

            $i_tab++;
        }
    }
}
