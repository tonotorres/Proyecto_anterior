<?php

use Illuminate\Database\Seeder;

class NP00014PwaElementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $module_key = 12;

        //RC: Generamos el registro del módulo
        if( \App\Module::where('key', $module_key)->count() == 0) {
            \App\Module::create([
                'key' => $module_key,
                'name' => 'PWA / Elementos',
                'url' => 'pwa_elements',
                'help' => 'help/pwa_elements'
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
                                'label' => 'Título',
                                'name' => 'title',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required|string|max:50',
                                'validations_update' => 'required|string|max:50',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-12',
                                'label' => 'Contenido',
                                'name' => 'content',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required',
                                'validations_update' => 'required',
                                'is_simple_form' => false
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-12',
                                'label' => 'Posición',
                                'name' => 'position',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required|numeric|min:0',
                                'validations_update' => 'required|numeric|min:0',
                                'is_simple_form' => false
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-12',
                                'label' => 'Visibilidad',
                                'name' => 'is_private',
                                'default' => '0',
                                'options' => "0:Pública\r\n1:Privada",
                                'validations_create' => 'nullable',
                                'validations_update' => 'nullable',
                                'is_simple_form' => true
                            ]
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
                                'label' => 'Página',
                                'name' => 'pwa_page_id',
                                'default' => null,
                                'options' => 'pwa_pages:id,name',
                                'validations_create' => 'required|exists:pwa_pages,id',
                                'validations_update' => 'required|exists:pwa_pages,id'
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-12',
                                'label' => 'Tipo de elemento',
                                'name' => 'pwa_element_type_id',
                                'default' => null,
                                'options' => 'pwa_element_types:id,name',
                                'validations_create' => 'required|exists:pwa_element_types,id',
                                'validations_update' => 'required|exists:pwa_element_types,id'
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-12',
                                'label' => 'Idioma',
                                'name' => 'pwa_language_id',
                                'default' => null,
                                'options' => 'pwa_languages:id,name',
                                'validations_create' => 'required|exists:pwa_languages,id',
                                'validations_update' => 'required|exists:pwa_languages,id'
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
