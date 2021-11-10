<?php

use Illuminate\Database\Seeder;

class NP00005AccountTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $module_key = 5;

        //RC: Creamos un tipo de cuentas
        \App\AccountType::create([
            'id' => 1,
            'company_id' => null,
            'name' => 'Cliente',
        ]);
        \App\AccountType::create([
            'id' => 2,
            'company_id' => null,
            'name' => 'Proveedor',
        ]);

        //RC: Generamos el registro del módulo
        \App\Module::create([
            'key' => $module_key,
            'name' => 'Tipos de cuentas',
            'url' => 'account_types',
            'help' => 'help/account_types'
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
                                'field_type_id' => 8,
                                'width' => 'is-12',
                                'label' => 'Organización',
                                'name' => 'company_id',
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
