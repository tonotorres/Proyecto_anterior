<?php

use Illuminate\Database\Seeder;

class NP00067CampaignContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $module_key = 58;

        //RC: Generamos el registro del módulo
        if (\App\Module::where('key', $module_key)->count() == 0) {
            \App\Module::create([
                'key' => $module_key,
                'name' => 'Contactos de la campaña',
                'url' => 'campaign_contacts',
                'help' => 'help/campaign_contacts'
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
                                'validations_create' => 'nullable|string|min:0|max:100',
                                'validations_update' => 'nullable|string|min:0|max:100',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-12',
                                'label' => 'Apelldios',
                                'name' => 'last_name',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|string|min:0|max:100',
                                'validations_update' => 'nullable|string|min:0|max:100',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-12',
                                'label' => 'Fecha de nacimiento',
                                'name' => 'birthday',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|string|min:0|max:100',
                                'validations_update' => 'nullable|string|min:0|max:100',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-12',
                                'label' => 'NIF',
                                'name' => 'nif',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|string|min:0|max:36',
                                'validations_update' => 'nullable|string|min:0|max:36',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-12',
                                'label' => 'Teléfono 1',
                                'name' => 'phone_1',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|string|min:0|max:36',
                                'validations_update' => 'nullable|string|min:0|max:36',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-12',
                                'label' => 'Teléfono 2',
                                'name' => 'phone_2',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|string|min:0|max:36',
                                'validations_update' => 'nullable|string|min:0|max:36',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-12',
                                'label' => 'Email 1',
                                'name' => 'email_1',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|string|min:0|max:100',
                                'validations_update' => 'nullable|string|min:0|max:100',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-12',
                                'label' => 'Email 2',
                                'name' => 'email_2',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|string|min:0|max:100',
                                'validations_update' => 'nullable|string|min:0|max:100',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-12',
                                'label' => 'Dirección',
                                'name' => 'address',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|string|min:0|max:100',
                                'validations_update' => 'nullable|string|min:0|max:100',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-12',
                                'label' => 'Dirección (aux)',
                                'name' => 'address_aux',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|string|min:0|max:100',
                                'validations_update' => 'nullable|string|min:0|max:100',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-12',
                                'label' => 'CP',
                                'name' => 'postal_code',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|string|min:0|max:16',
                                'validations_update' => 'nullable|string|min:0|max:16',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-12',
                                'label' => 'Población',
                                'name' => 'location',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|string|min:0|max:50',
                                'validations_update' => 'nullable|string|min:0|max:50',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-12',
                                'label' => 'Región',
                                'name' => 'region',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|string|min:0|max:50',
                                'validations_update' => 'nullable|string|min:0|max:50',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-12',
                                'label' => 'País',
                                'name' => 'country',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|string|min:0|max:50',
                                'validations_update' => 'nullable|string|min:0|max:50',
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
