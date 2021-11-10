<?php

use Illuminate\Database\Seeder;

class NP00043ReportItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $module_key = 37;

        //RC: Generamos el registro del módulo
        if( \App\Module::where('key', $module_key)->count() == 0) {
            \App\Module::create([
                'key' => $module_key,
                'name' => 'Informes / items',
                'url' => 'report_items',
                'help' => 'help/report_items'
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
                                'label' => 'Grupo de impuestos',
                                'name' => 'report_type_id',
                                'default' => null,
                                'options' => 'report_types:id,name',
                                'validations_create' => 'required|exists:report_types,id',
                                'validations_update' => 'required|exists:report_types,id',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-12',
                                'label' => 'Nombre',
                                'name' => 'name',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required|max:100',
                                'validations_update' => 'required|max:100',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-4',
                                'label' => 'Ancho del elemento',
                                'name' => 'report_type_id',
                                'default' => null,
                                'options' => "col-12:1/1 columna\r\ncol-6:1/2 columna\r\ncol-4:1/3 columna",
                                'validations_create' => 'required',
                                'validations_update' => 'required',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-4',
                                'label' => 'Posición',
                                'name' => 'position',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required|min:0',
                                'validations_update' => 'required|min:0',
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
