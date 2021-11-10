<?php

use Illuminate\Database\Seeder;

class NP00018TextSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $module_key = 18;

        //RC: Generamos el registro del módulo
        \App\Module::create([
            'key' => $module_key,
            'name' => 'Textos',
            'url' => 'texts',
            'help' => 'help/texts'
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
                                'label' => 'Clave',
                                'name' => 'key',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required|string|max:100',
                                'validations_update' => 'required|string|max:100',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 11,
                                'width' => 'is-12',
                                'label' => 'Texto',
                                'name' => 'text',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable',
                                'validations_update' => 'nullable',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-12',
                                'label' => 'Texto enriquecido',
                                'name' => 'is_html',
                                'default' => '0',
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
