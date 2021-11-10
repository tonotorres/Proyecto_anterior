<?php

use App\Tax;
use Illuminate\Database\Seeder;

class NP00024TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $module_key = 24;

        //RC: Generamos el registro del módulo
        \App\Module::create([
            'key' => $module_key,
            'name' => 'Impuestos',
            'url' => 'taxes',
            'help' => 'help/taxes'
        ]);

        Tax::create([
            'tax_group_id' => 1,
            'name' => 'IVA 21%',
            'value' => 21
        ]);
        Tax::create([
            'tax_group_id' => 1,
            'name' => 'IVA 10%',
            'value' => 10
        ]);
        Tax::create([
            'tax_group_id' => 2,
            'name' => 'RE 5.2%',
            'value' => 5.2
        ]);
        Tax::create([
            'tax_group_id' => 2,
            'name' => 'RE 1.4%',
            'value' => 1.4
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
                                'width' => 'is-12',
                                'label' => 'Grupo de impuestos',
                                'name' => 'tax_group_id',
                                'default' => null,
                                'options' => 'tax_groups:id,name',
                                'validations_create' => 'required|exists:tax_groups,id',
                                'validations_update' => 'required|exists:tax_groups,id',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-6',
                                'label' => 'Nombre',
                                'name' => 'name',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required|string|max:100',
                                'validations_update' => 'required|string|max:100',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 5,
                                'width' => 'is-6',
                                'label' => 'Valor',
                                'name' => 'value',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required|numeric|min:0',
                                'validations_update' => 'required|numeric|min:0',
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
