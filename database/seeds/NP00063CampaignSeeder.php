<?php

use Illuminate\Database\Seeder;

class NP00063CampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $module_key = 54;

        //RC: Generamos el registro del módulo
        if (\App\Module::where('key', $module_key)->count() == 0) {
            \App\Module::create([
                'key' => $module_key,
                'name' => 'Campañas',
                'url' => 'campaigns',
                'help' => 'help/campaigns'
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
                                'width' => 'is-12',
                                'label' => 'Cuenta',
                                'name' => 'account_id',
                                'default' => null,
                                'options' => 'accounts:id,name',
                                'validations_create' => 'nullable|exists:campaigns,id',
                                'validations_update' => 'nullable|exists:campaigns,id',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-3',
                                'label' => 'Código',
                                'name' => 'code',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|string|min:0|max:36',
                                'validations_update' => 'nullable|string|min:0|max:36',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-9',
                                'label' => 'Nombre',
                                'name' => 'name',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|string|min:0|max:100',
                                'validations_update' => 'nullable|string|min:0|max:100',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 15,
                                'width' => 'is-6',
                                'label' => 'Fecha de inicio',
                                'name' => 'start',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|date',
                                'validations_update' => 'nullable|date',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 15,
                                'width' => 'is-6',
                                'label' => 'Fecha de fin',
                                'name' => 'end',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|date',
                                'validations_update' => 'nullable|date',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 9,
                                'width' => 'is-12',
                                'label' => 'Formularios',
                                'name' => 'campaign_forms_id',
                                'default' => null,
                                'options' => 'campaign_forms:id,name',
                                'validations_create' => 'nullable',
                                'validations_update' => 'nullable',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 9,
                                'width' => 'is-12',
                                'label' => 'Finales',
                                'name' => 'campaign_answer_ends_id',
                                'default' => null,
                                'options' => 'campaign_answer_ends:id,name',
                                'validations_create' => 'nullable',
                                'validations_update' => 'nullable',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 9,
                                'width' => 'is-12',
                                'label' => 'Usuarios',
                                'name' => 'users_id',
                                'default' => null,
                                'options' => 'users:id,name',
                                'validations_create' => 'nullable',
                                'validations_update' => 'nullable',
                                'is_simple_form' => true
                            ],
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Campaña de llamadas de entrada',
                'sections' =>  [
                    [
                        'name' => 'Información general',
                        'fields' => [
                            [
                                'field_type_id' => 15,
                                'width' => 'is-6',
                                'label' => 'Fecha de inicio',
                                'name' => 'campaignInCallStart',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|date',
                                'validations_update' => 'nullable|date',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 15,
                                'width' => 'is-6',
                                'label' => 'Fecha de fin',
                                'name' => 'campaignInCallEnd',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|date',
                                'validations_update' => 'nullable|date',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-6',
                                'label' => 'Cola de entrada',
                                'name' => 'campaignInCallQueue',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|string',
                                'validations_update' => 'nullable|string',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 3,
                                'width' => 'is-6',
                                'label' => 'Tiempo administrativo en segundos',
                                'name' => 'campaignInCallAdministrativeTime',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|numeric|min:0',
                                'validations_update' => 'nullable|numeric|min:0',
                                'is_simple_form' => true
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Campaña de llamadas de salida',
                'sections' =>  [
                    [
                        'name' => 'Información general',
                        'fields' => [
                            [
                                'field_type_id' => 8,
                                'width' => 'is-12',
                                'label' => 'Rutas salientes',
                                'name' => 'campaignOutCallRouteOutId',
                                'default' => null,
                                'options' => 'route_outs:id,name',
                                'validations_create' => 'nullable',
                                'validations_update' => 'nullable',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 15,
                                'width' => 'is-6',
                                'label' => 'Fecha de inicio',
                                'name' => 'campaignOutCallStart',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|date',
                                'validations_update' => 'nullable|date',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 15,
                                'width' => 'is-6',
                                'label' => 'Fecha de fin',
                                'name' => 'campaignOutCallEnd',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|date',
                                'validations_update' => 'nullable|date',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 17,
                                'width' => 'is-6',
                                'label' => 'Hora de inicio para llamar (HH:mm)',
                                'name' => 'campaignOutCallStartTime',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|string',
                                'validations_update' => 'nullable|string',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 17,
                                'width' => 'is-6',
                                'label' => 'Hora de fin para llamar (HH:mm)',
                                'name' => 'campaignOutCallEndTime',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|string',
                                'validations_update' => 'nullable|string',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 3,
                                'width' => 'is-12',
                                'label' => 'Tiempo administrativo en segundos',
                                'name' => 'campaignOutCallAdministrativeTime',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|numeric|min:0',
                                'validations_update' => 'nullable|numeric|min:0',
                                'is_simple_form' => true
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
