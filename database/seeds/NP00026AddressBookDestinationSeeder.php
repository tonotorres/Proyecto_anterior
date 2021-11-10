<?php

use Illuminate\Database\Seeder;

class NP00026AddressBookDestinationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $module_key = 26;

        //RC: Generamos el registro del módulo
        \App\Module::create([
            'key' => $module_key,
            'name' => 'Agenda de destinos > Destinos',
            'url' => 'address_book_destinations',
            'help' => 'help/address_book_destinations'
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
                                'field_type_id' => 8,
                                'width' => 'is-6',
                                'label' => 'Agenda',
                                'name' => 'address_book_id',
                                'default' => null,
                                'options' => 'address_books:id,name',
                                'validations_create' => 'required|exists:address_books,id',
                                'validations_update' => 'required|exists:address_books,id',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-6',
                                'label' => 'Módulo',
                                'name' => 'module_id',
                                'default' => null,
                                'options' => 'modules:id,name',
                                'validations_create' => 'required|exists:modules,id',
                                'validations_update' => 'required|exists:modules,id',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-6',
                                'label' => 'Reference Id',
                                'name' => 'reference_id',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required',
                                'validations_update' => 'required',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-6',
                                'label' => 'Destino',
                                'name' => 'destination',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required',
                                'validations_update' => 'required',
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
