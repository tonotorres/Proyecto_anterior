<?php

use Illuminate\Database\Seeder;

class NP00021AccountAddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $module_key = 21;

        //RC: Generamos el registro del módulo
        \App\Module::create([
            'key' => $module_key,
            'name' => 'Cuentas / Direcciones',
            'url' => 'account_addresses',
            'help' => 'help/account_addresses'
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
                                'width' => 'is-5',
                                'label' => 'Calle',
                                'name' => 'address',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required|string|max:100',
                                'validations_update' => 'required|string|max:100',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-2',
                                'label' => 'Número',
                                'name' => 'number',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable|string|max:32',
                                'validations_update' => 'nullable|string|max:32',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-5',
                                'label' => 'Bloque, piso, escalera, puerta...',
                                'name' => 'address_aux',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required|string|max:100',
                                'validations_update' => 'required|string|max:100',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-3',
                                'label' => 'Código Postal',
                                'name' => 'postal_code',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required|string|max:32',
                                'validations_update' => 'required|string|max:32',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-3',
                                'label' => 'Localidad',
                                'name' => 'location',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required|string|max:100',
                                'validations_update' => 'required|string|max:100',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-3',
                                'label' => 'Región',
                                'name' => 'region_id',
                                'default' => null,
                                'options' => 'regions:id,name',
                                'validations_create' => 'nullable|exists:regions,id',
                                'validations_update' => 'nullable|exists:regions,id',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-3',
                                'label' => 'País',
                                'name' => 'country_id',
                                'default' => null,
                                'options' => 'countries:id,name',
                                'validations_create' => 'nullable|exists:countries,id',
                                'validations_update' => 'nullable|exists:countries,id',
                                'is_simple_form' => true
                            ]
                        ]
                    ]
                ]
            ]
        ];

        //RC: obtenemos el registro del módulo
        $module = \App\Module::where('key', $module_key)->first();

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
