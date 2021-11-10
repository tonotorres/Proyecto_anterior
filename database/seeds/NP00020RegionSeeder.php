<?php

use Illuminate\Database\Seeder;

class NP00020RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $module_key = 20;

        //RC: Generamos el registro del módulo
        \App\Module::create([
            'key' => $module_key,
            'name' => 'Regiones',
            'url' => 'regions',
            'help' => 'help/regions'
        ]);

        \App\Region::create(['country_id' => 1, 'name' => 'Álava']);
        \App\Region::create(['country_id' => 1, 'name' => 'Albacete']);
        \App\Region::create(['country_id' => 1, 'name' => 'Alicante']);
        \App\Region::create(['country_id' => 1, 'name' => 'Almería']);
        \App\Region::create(['country_id' => 1, 'name' => 'Asturias']);
        \App\Region::create(['country_id' => 1, 'name' => 'Ávila']);
        \App\Region::create(['country_id' => 1, 'name' => 'Badajoz']);
        \App\Region::create(['country_id' => 1, 'name' => 'Barcelona']);
        \App\Region::create(['country_id' => 1, 'name' => 'Burgos']);
        \App\Region::create(['country_id' => 1, 'name' => 'Cáceres']);
        \App\Region::create(['country_id' => 1, 'name' => 'Cádiz']);
        \App\Region::create(['country_id' => 1, 'name' => 'Cantabria']);
        \App\Region::create(['country_id' => 1, 'name' => 'Castellón']);
        \App\Region::create(['country_id' => 1, 'name' => 'Ciudad Real']);
        \App\Region::create(['country_id' => 1, 'name' => 'Córdoba']);
        \App\Region::create(['country_id' => 1, 'name' => 'La Coruña']);
        \App\Region::create(['country_id' => 1, 'name' => 'Cuenca']);
        \App\Region::create(['country_id' => 1, 'name' => 'Gerona']);
        \App\Region::create(['country_id' => 1, 'name' => 'Granada']);
        \App\Region::create(['country_id' => 1, 'name' => 'Guadalajara']);
        \App\Region::create(['country_id' => 1, 'name' => 'Guipúzcoa']);
        \App\Region::create(['country_id' => 1, 'name' => 'Huelva']);
        \App\Region::create(['country_id' => 1, 'name' => 'Huesca']);
        \App\Region::create(['country_id' => 1, 'name' => 'Baleares']);
        \App\Region::create(['country_id' => 1, 'name' => 'Jaén']);
        \App\Region::create(['country_id' => 1, 'name' => 'León']);
        \App\Region::create(['country_id' => 1, 'name' => 'Lérida']);
        \App\Region::create(['country_id' => 1, 'name' => 'Lugo']);
        \App\Region::create(['country_id' => 1, 'name' => 'Madrid']);
        \App\Region::create(['country_id' => 1, 'name' => 'Málaga']);
        \App\Region::create(['country_id' => 1, 'name' => 'Murcia']);
        \App\Region::create(['country_id' => 1, 'name' => 'Navarra']);
        \App\Region::create(['country_id' => 1, 'name' => 'Orense']);
        \App\Region::create(['country_id' => 1, 'name' => 'Palencia']);
        \App\Region::create(['country_id' => 1, 'name' => 'Las Palmas']);
        \App\Region::create(['country_id' => 1, 'name' => 'Pontevedra']);
        \App\Region::create(['country_id' => 1, 'name' => 'La Rioja']);
        \App\Region::create(['country_id' => 1, 'name' => 'Salamanca']);
        \App\Region::create(['country_id' => 1, 'name' => 'Segovia']);
        \App\Region::create(['country_id' => 1, 'name' => 'Sevilla']);
        \App\Region::create(['country_id' => 1, 'name' => 'Soria']);
        \App\Region::create(['country_id' => 1, 'name' => 'Tarragona']);
        \App\Region::create(['country_id' => 1, 'name' => 'Santa Cruz de Tenerife']);
        \App\Region::create(['country_id' => 1, 'name' => 'Teruel']);
        \App\Region::create(['country_id' => 1, 'name' => 'Toledo']);
        \App\Region::create(['country_id' => 1, 'name' => 'Valencia']);
        \App\Region::create(['country_id' => 1, 'name' => 'Valladolid']);
        \App\Region::create(['country_id' => 1, 'name' => 'Vizcaya']);
        \App\Region::create(['country_id' => 1, 'name' => 'Zamora']);
        \App\Region::create(['country_id' => 1, 'name' => 'Zaragoza']);
        \App\Region::create(['country_id' => 1, 'name' => 'Ceuta']);
        \App\Region::create(['country_id' => 1, 'name' => 'Melilla']);

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
                                'width' => 'is-4',
                                'label' => 'Código',
                                'name' => 'code',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required|string|max:32',
                                'validations_update' => 'required|string|max:32',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-4',
                                'label' => 'Nombre',
                                'name' => 'name',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required|string|max:100',
                                'validations_update' => 'required|string|max:100',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-4',
                                'label' => 'País',
                                'name' => 'country_id',
                                'default' => null,
                                'options' => 'countries:id,name',
                                'validations_create' => 'required|exists:countries,id',
                                'validations_update' => 'required|exists:countries,id',
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
