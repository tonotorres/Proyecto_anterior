<?php

use App\User;
use Illuminate\Database\Seeder;

class NP00004UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $module_key = 4;
        //RC: Creamos un tipo de usuario

        if(User::where('id', 1)->count() == 0) {
            $user = \App\User::create([
                'id' => 1,
                'company_id' => 1,
                'user_template_id' => 1,
                'user_type_id' => 1,
                'name' => 'Superadministrador',
                'email' => 'superadmin@superadmin.com',
                'username' => 'superadmin',
                'password' => bcrypt('superadmin'),
                'is_active' => '1'
            ]);

            $user->companies()->attach(1);
            $user->companies()->attach(2);
        }


        //RC: Generamos el registro del módulo
        if( \App\Module::where('key', $module_key)->count() == 0) {
            \App\Module::create([
                'key' => $module_key,
                'name' => 'Usuarios',
                'url' => 'users',
                'help' => 'help/users'
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
                                'field_type_id' => 8,
                                'width' => 'is-4',
                                'label' => 'Tipo de usuario',
                                'name' => 'user_type_id',
                                'default' => null,
                                'options' => 'user_types:id,name',
                                'validations_create' => 'required|exists:user_types,id',
                                'validations_update' => 'required|exists:user_types,id',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 8,
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
                                'field_type_id' => 8,
                                'width' => 'is-4',
                                'label' => 'Departamento',
                                'name' => 'department_id',
                                'default' => null,
                                'options' => 'departments:id,name',
                                'validations_create' => 'nullable|exists:departments,id',
                                'validations_update' => 'nullable|exists:departments,id',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-12',
                                'label' => 'Nombre',
                                'name' => 'name',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required|string|max:100',
                                'validations_update' => 'required|string|max:100',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 6,
                                'width' => 'is-8',
                                'label' => 'Email',
                                'name' => 'email',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|email|max:100',
                                'validations_update' => 'nullable|email|max:100',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-4',
                                'label' => 'Extensión',
                                'name' => 'extension',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|string|max:32',
                                'validations_update' => 'nullable|string|max:32',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-4',
                                'label' => 'Login',
                                'name' => 'username',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required|string|unique:users,username|max:100',
                                'validations_update' => 'required|string|unique:users,username,##ID##|max:100',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 7,
                                'width' => 'is-4',
                                'label' => 'Contraseña',
                                'name' => 'password',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required|min:8|max:32',
                                'validations_update' => 'nullable|min:8|max:32',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-4',
                                'label' => 'Mantener siempre conectado',
                                'name' => 'always_online',
                                'default' => "0",
                                'options' => "0:No\r\n1:Si",
                                'validations_create' => 'nullable',
                                'validations_update' => 'nullable',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-12',
                                'label' => 'Descanso inicial',
                                'name' => 'signin_break_time_id',
                                'default' => null,
                                'options' => 'break_times:id,name',
                                'validations_create' => 'nullable|exists:break_times,id',
                                'validations_update' => 'nullable|exists:break_times,id',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 9,
                                'width' => 'is-12',
                                'label' => 'Extensiones disponibles (sólo si dispone de más de una)',
                                'name' => 'extensions',
                                'default' => null,
                                'options' => 'extensionNumbers:id,name',
                                'validations_create' => 'nullable',
                                'validations_update' => 'nullable',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 12,
                                'width' => 'is-12',
                                'label' => 'Imagen',
                                'name' => 'image',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable',
                                'validations_update' => 'nullable',
                                'is_simple_form' => true
                            ],
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Avanzado',
                'sections' =>  [
                    [
                        'name' => 'Organización',
                        'fields' => [
                            [
                                'field_type_id' => 9,
                                'width' => 'is-12',
                                'label' => 'Organización',
                                'name' => 'companies_id',
                                'default' => null,
                                'options' => 'companies:id,name',
                                'validations_create' => 'nullable|exists:companies,id',
                                'validations_update' => 'nullable|exists:companies,id'
                            ]
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
