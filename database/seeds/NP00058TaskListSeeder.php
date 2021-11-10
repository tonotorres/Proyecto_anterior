<?php

use Illuminate\Database\Seeder;

class NP00058TaskListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $module_key = 50;

        //RC: Generamos el registro del módulo
        if( \App\Module::where('key', $module_key)->count() == 0) {
            \App\Module::create([
                'key' => $module_key,
                'name' => 'Listados de tareas',
                'url' => 'task_lists',
                'help' => 'help/task_lists'
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
                                'field_type_id' => 2,
                                'width' => 'is-12',
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
                                'width' => 'is-6',
                                'label' => 'Cuenta',
                                'name' => 'account_id',
                                'default' => null,
                                'options' => 'accounts:id,name',
                                'validations_create' => 'nullable|exists:accounts,id',
                                'validations_update' => 'nullable|exists:accounts,id',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-6',
                                'label' => 'Proyecto',
                                'name' => 'project_id',
                                'default' => null,
                                'options' => 'projects:id,name',
                                'validations_create' => 'nullable|exists:projects,id',
                                'validations_update' => 'nullable|exists:projects,id',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 9,
                                'width' => 'is-12',
                                'label' => 'Users',
                                'name' => 'users_id',
                                'default' => null,
                                'options' => 'users:id,name',
                                'validations_create' => 'nullable|exists:users,id',
                                'validations_update' => 'nullable|exists:users,id',
                                'is_simple_form' => true
                            ],
                        ]
                    ]
                ]
            ]
        ];

        //RC: obtenemos el registro del módulo
        $module = \App\Module::where('key', $module_key)->first();

        \App\Tab::where('module_id', $module->id)->delete();

        //RC: Generamos la estructura de los formularios
        $i_tab = 0;
        foreach($tabs as $tab) {
            $tab['module_id'] = $module->id;
            $tab['position'] = $i_tab;

            $sections = $tab['sections'];
            unset($tab['sections']);
            $tabReg = \App\Tab::create($tab);
            if(!empty($sections)) {
                $i_section = 0;

                foreach($sections as $section) {
                    $section['tab_id'] = $tabReg->id;
                    $section['position'] = $i_section;

                    $fields = $section['fields'];
                    unset($section['fields']);
                    $sectionReg = \App\Section::create($section);
                    if(!empty($fields)) {
                        $i_field = 0;
                        foreach($fields as $field) {
                            $field['section_id'] = $sectionReg->id;
                            $field['key'] = $module->key.'_'.$field['name'];
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
