<?php

use App\Company;
use App\ProjectStageStatus;
use Illuminate\Database\Seeder;

class NP00054ProjectStageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $module_key = 46;

        //RC: Generamos el registro del módulo
        if( \App\Module::where('key', $module_key)->count() == 0) {
            \App\Module::create([
                'key' => $module_key,
                'name' => 'Etapas de proyecto',
                'url' => 'project_stages',
                'help' => 'help/project_stages'
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
                                'width' => 'is-3',
                                'label' => 'Nombre',
                                'name' => 'name',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required|string|max:50',
                                'validations_update' => 'required|string|max:50',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-3',
                                'label' => 'Estado',
                                'name' => 'project_stage_status_id',
                                'default' => null,
                                'options' => "project_stage_statuses:id,name",
                                'validations_create' => 'required|exists:project_stage_statuses,id',
                                'validations_update' => 'required|exists:project_stage_statuses,id',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 15,
                                'width' => 'is-3',
                                'label' => 'Fecha de inicio',
                                'name' => 'start',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable',
                                'validations_update' => 'nullable',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 15,
                                'width' => 'is-3',
                                'label' => 'Fecha de fin',
                                'name' => 'end',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable',
                                'validations_update' => 'nullable',
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

        $companies = Company::all();
        foreach ($companies as $company) {
            if (ProjectStageStatus::where('name', 'Pendiente')->where('company_id', $company->id)->count() == 0) {
                ProjectStageStatus::create([
                    'company_id' => $company->id,
                    'name' => 'Pendiente',
                    'weight' => 10,
                    'color' => '#181c32'
                ]);
            }

            if (ProjectStageStatus::where('name', 'En proceso')->where('company_id', $company->id)->count() == 0) {
                ProjectStageStatus::create([
                    'company_id' => $company->id,
                    'name' => 'En proceso',
                    'weight' => 30,
                    'color' => '#3699ff'
                ]);
            }

            if (ProjectStageStatus::where('name', 'Finalizada')->where('company_id', $company->id)->count() == 0) {
                ProjectStageStatus::create([
                    'company_id' => $company->id,
                    'name' => 'Finalizada',
                    'weight' => 90,
                    'color' => '#1bc5bd',
                    'finish' => 1
                ]);
            }

            if (ProjectStageStatus::where('name', 'Cancelada')->where('company_id', $company->id)->count() == 0) {
                ProjectStageStatus::create([
                    'company_id' => $company->id,
                    'name' => 'Cancelada',
                    'weight' => 5,
                    'color' => '#181c32',
                    'finish' => 1
                ]);
            }
        }
    }
}
