<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NP00001CompaniesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //RC: Creamos la primera empresa
        \App\Company::create([
            'id' => 1,
            'name' => 'Empresa 1',
            'logo' => null
            ]);

        \App\Company::create([
                'id' => 2,
                'name' => 'Empresa 2',
                'logo' => null
            ]);

        //RC: Generamos el registro del módulo
        \App\Module::create([
            'key' => 1,
            'name' => 'Organización',
            'url' => 'companies',
            'help' => 'help/companies'
        ]);

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
                                'field_type_id' => 12,
                                'width' => 'is-12',
                                'label' => 'Logotipo',
                                'name' => 'logo',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|image',
                                'validations_update' => 'nullable|image',
                            ],
                        ]
                    ]
                ]
            ],
            [
                'name' => 'PBX',
                'sections' =>  [
                    [
                        'name' => 'API',
                        'fields' => [
                            [
                                'field_type_id' => 2,
                                'width' => 'is-9',
                                'label' => 'Host',
                                'name' => 'api_host',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|string',
                                'validations_update' => 'nullable|string',
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-3',
                                'label' => 'Puerto',
                                'name' => 'api_port',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required_with:api_host|numeric',
                                'validations_update' => 'required_with:api_host|numeric',
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-6',
                                'label' => 'Usuario',
                                'name' => 'api_user',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required_with:api_host|string',
                                'validations_update' => 'required_with:api_host|string',
                            ],
                            [
                                'field_type_id' => 7,
                                'width' => 'is-6',
                                'label' => 'Password',
                                'name' => 'api_password',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required_with:api_host|string',
                                'validations_update' => 'required_with:api_host|string',
                            ],
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Email',
                'sections' =>  [
                    [
                        'name' => 'Configuración Email',
                        'fields' => [
                            [
                                'field_type_id' => 2,
                                'width' => 'is-6',
                                'label' => 'Host (IMAP)',
                                'name' => 'email_imap_host',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|string',
                                'validations_update' => 'nullable|string',
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-3',
                                'label' => 'Puerto',
                                'name' => 'email_imap_port',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required_with:email_imap_host|numeric',
                                'validations_update' => 'required_with:email_imap_host|numeric',
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-3',
                                'label' => 'Seguridad',
                                'name' => 'email_imap_security',
                                'default' => null,
                                'options' => "false:Sin encriptar\r\nssl:SSL\r\ntls:TLS",
                                'validations_create' => 'required_with:email_imap_security',
                                'validations_update' => 'required_with:email_imap_security',
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-6',
                                'label' => 'Usuario',
                                'name' => 'email_imap_user',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required_with:email_imap_host|string',
                                'validations_update' => 'required_with:email_imap_host|string',
                            ],
                            [
                                'field_type_id' => 7,
                                'width' => 'is-6',
                                'label' => 'Password',
                                'name' => 'email_imap_password',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required_with:email_imap_host|string',
                                'validations_update' => 'required_with:email_imap_host|string',
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-6',
                                'label' => 'Host (SMTP)',
                                'name' => 'email_smtp_host',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required_with:email_imap_host|string',
                                'validations_update' => 'required_with:email_imap_host|string',
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-3',
                                'label' => 'Puerto',
                                'name' => 'email_smtp_port',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required_with:email_imap_host|numeric',
                                'validations_update' => 'required_with:email_imap_host|numeric',
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-3',
                                'label' => 'Seguridad',
                                'name' => 'email_smtp_security',
                                'default' => null,
                                'options' => "false:Sin encriptar\r\nssl:SSL\r\ntls:TLS",
                                'validations_create' => 'required_with:email_imap_host',
                                'validations_update' => 'required_with:email_imap_host',
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-6',
                                'label' => 'Usuario',
                                'name' => 'email_smtp_user',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required_with:email_imap_host|string',
                                'validations_update' => 'required_with:email_imap_host|string',
                            ],
                            [
                                'field_type_id' => 7,
                                'width' => 'is-6',
                                'label' => 'Password',
                                'name' => 'email_smtp_password',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required_with:email_imap_host|string',
                                'validations_update' => 'required_with:email_imap_host|string',
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Whatsapp',
                'sections' =>  [
                    [
                        'name' => 'Número de whatsapp genérico',
                        'fields' => [
                            [
                                'field_type_id' => 2,
                                'width' => 'is-12',
                                'label' => 'Número',
                                'name' => 'whatsapp_number',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|string',
                                'validations_update' => 'nullable|string',
                            ]
                        ]
                    ]
                ]
            ]
        ];

        //RC: obtenemos el registro del módulo
        $module = \App\Module::where('key', '1')->first();

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
