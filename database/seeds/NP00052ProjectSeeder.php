<?php

use App\Company;
use App\ProjectPriority;
use App\ProjectStatus;
use Illuminate\Database\Seeder;

class NP00052ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $module_key = 44;

        //RC: Generamos el registro del módulo
        if( \App\Module::where('key', $module_key)->count() == 0) {
            \App\Module::create([
                'key' => $module_key,
                'name' => 'Proyecto',
                'url' => 'projects',
                'help' => 'help/projects'
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
                                'label' => 'Código',
                                'name' => 'code',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required|string|max:50',
                                'validations_update' => 'required|string|max:50',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 2,
                                'width' => 'is-9',
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
                                'width' => 'is-4',
                                'label' => 'Cuenta',
                                'name' => 'account_id',
                                'default' => null,
                                'options' => "ajax:searchVselectAccounts",
                                'validations_create' => 'nullable',
                                'validations_update' => 'nullable',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-4',
                                'label' => 'Prioridad',
                                'name' => 'project_priority_id',
                                'default' => null,
                                'options' => "project_priorities:id,name",
                                'validations_create' => 'required|exists:project_priorities,id',
                                'validations_update' => 'required|exists:project_priorities,id',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-4',
                                'label' => 'Estado',
                                'name' => 'project_status_id',
                                'default' => null,
                                'options' => "project_statuses:id,name",
                                'validations_create' => 'required|exists:project_statuses,id',
                                'validations_update' => 'required|exists:project_statuses,id',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 15,
                                'width' => 'is-6',
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
                                'width' => 'is-6',
                                'label' => 'Fecha de fin',
                                'name' => 'end',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'nullable',
                                'validations_update' => 'nullable',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 9,
                                'width' => 'is-12',
                                'label' => 'Usuarios',
                                'name' => 'users_id',
                                'default' => null,
                                'options' => "users:id,name",
                                'validations_create' => 'nullable',
                                'validations_update' => 'nullable',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 11,
                                'width' => 'is-12',
                                'label' => 'Descripción',
                                'name' => 'description',
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
            //RC: Añadimos las prioridades
            if (ProjectPriority::where('name', 'Urgente')->where('company_id', $company->id)->count() == 0) {
                ProjectPriority::create([
                    'company_id' => $company->id,
                    'name' => 'Urgente',
                    'weight' => 90,
                    'color' => '#f64e60'
                ]);
            }

            if (ProjectPriority::where('name', 'Alta')->where('company_id', $company->id)->count() == 0) {
                ProjectPriority::create([
                    'company_id' => $company->id,
                    'name' => 'Alta',
                    'weight' => 80,
                    'color' => '#7337ee'
                ]);
            }

            if (ProjectPriority::where('name', 'Media')->where('company_id', $company->id)->count() == 0) {
                ProjectPriority::create([
                    'company_id' => $company->id,
                    'name' => 'Media',
                    'weight' => 75,
                    'color' => '#ffa800'
                ]);
            }

            if (ProjectPriority::where('name', 'Normal')->where('company_id', $company->id)->count() == 0) {
                ProjectPriority::create([
                    'company_id' => $company->id,
                    'name' => 'Normal',
                    'weight' => 50,
                    'color' => '#3699ff'
                ]);
            }

            if (ProjectStatus::where('name', 'Pendiente')->where('company_id', $company->id)->count() == 0) {
                ProjectStatus::create([
                    'company_id' => $company->id,
                    'name' => 'Pendiente',
                    'weight' => 10,
                    'color' => '#181c32'
                ]);
            }

            if (ProjectStatus::where('name', 'En proceso')->where('company_id', $company->id)->count() == 0) {
                ProjectStatus::create([
                    'company_id' => $company->id,
                    'name' => 'En proceso',
                    'weight' => 30,
                    'color' => '#3699ff'
                ]);
            }

            if (ProjectStatus::where('name', 'Finalizado')->where('company_id', $company->id)->count() == 0) {
                ProjectStatus::create([
                    'company_id' => $company->id,
                    'name' => 'Finalizado',
                    'weight' => 90,
                    'color' => '#1bc5bd',
                    'finish' => 1
                ]);
            }

            if (ProjectStatus::where('name', 'Cancelado')->where('company_id', $company->id)->count() == 0) {
                ProjectStatus::create([
                    'company_id' => $company->id,
                    'name' => 'Cancelado',
                    'weight' => 5,
                    'color' => '#181c32',
                    'finish' => 1
                ]);
            }
        }
    }
}
