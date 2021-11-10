<?php

use App\Company;
use App\CompanyConfig;
use App\CompanyConfigGroup;
use App\TaskPriority;
use App\TaskStatus;
use App\TaskType;
use Illuminate\Database\Seeder;

class NP00059TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $module_key = 51;

        //RC: Generamos el registro del módulo
        if( \App\Module::where('key', $module_key)->count() == 0) {
            \App\Module::create([
                'key' => $module_key,
                'name' => 'Tareas',
                'url' => 'tasks',
                'help' => 'help/tasks'
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
                                'label' => 'Nombre',
                                'name' => 'name',
                                'default' => null,
                                'options' => null,
                                'validations_create' => 'required|string|max:255',
                                'validations_update' => 'required|string|max:255',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-4',
                                'label' => 'Tipo de tarea',
                                'name' => 'task_type_id',
                                'default' => null,
                                'options' => 'task_types:id,name',
                                'validations_create' => 'required|exists:task_types,id',
                                'validations_update' => 'required|exists:task_types,id',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-4',
                                'label' => 'Prioridad',
                                'name' => 'task_priority_id',
                                'default' => null,
                                'options' => 'task_priorities:id,name',
                                'validations_create' => 'required|exists:task_priorities,id',
                                'validations_update' => 'required|exists:task_priorities,id',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-4',
                                'label' => 'Estado',
                                'name' => 'task_status_id',
                                'default' => null,
                                'options' => 'task_statuses:id,name',
                                'validations_create' => 'required|exists:task_statuses,id',
                                'validations_update' => 'required|exists:task_statuses,id',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-6',
                                'label' => 'Lista de tareas',
                                'name' => 'task_list_id',
                                'default' => null,
                                'options' => 'task_lists:id,name',
                                'validations_create' => 'nullable|exists:task_lists,id',
                                'validations_update' => 'nullable|exists:task_lists,id',
                                'is_simple_form' => true
                            ],
                            [
                                'field_type_id' => 8,
                                'width' => 'is-6',
                                'label' => 'Tarea padre',
                                'name' => 'task_id',
                                'default' => null,
                                'options' => 'tasks:id,name',
                                'validations_create' => 'nullable|exists:tasks,id',
                                'validations_update' => 'nullable|exists:tasks,id',
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
                                'label' => 'Users',
                                'name' => 'users_id',
                                'default' => null,
                                'options' => 'users:id,name',
                                'validations_create' => 'nullable|exists:users,id',
                                'validations_update' => 'nullable|exists:users,id',
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

        //RC: Obtenemos las empresas
        $companies = Company::all();
        foreach ($companies as $company) {
            //RC: Añadimos los tipos
            if (TaskType::where('name', 'Tarea')->where('company_id', $company->id)->count() == 0) {
                TaskType::create([
                    'company_id' => $company->id,
                    'name' => 'Tarea',
                    'color' => '#3699ff'
                ]);
            }

            if (TaskType::where('name', 'Tiquet')->where('company_id', $company->id)->count() == 0) {
                TaskType::create([
                    'company_id' => $company->id,
                    'name' => 'Tiquet',
                    'color' => '#f64e60'
                ]);
            }

            //RC: Añadimos las prioridades
            if (TaskPriority::where('name', 'Urgente')->where('company_id', $company->id)->count() == 0) {
                TaskPriority::create([
                    'company_id' => $company->id,
                    'name' => 'Urgente',
                    'weight' => 90,
                    'color' => '#f64e60'
                ]);
            }

            if (TaskPriority::where('name', 'Alta')->where('company_id', $company->id)->count() == 0) {
                TaskPriority::create([
                    'company_id' => $company->id,
                    'name' => 'Alta',
                    'weight' => 80,
                    'color' => '#7337ee'
                ]);
            }

            if (TaskPriority::where('name', 'Media')->where('company_id', $company->id)->count() == 0) {
                TaskPriority::create([
                    'company_id' => $company->id,
                    'name' => 'Media',
                    'weight' => 75,
                    'color' => '#ffa800'
                ]);
            }

            if (TaskPriority::where('name', 'Normal')->where('company_id', $company->id)->count() == 0) {
                TaskPriority::create([
                    'company_id' => $company->id,
                    'name' => 'Normal',
                    'weight' => 50,
                    'color' => '#3699ff'
                ]);
            }

            if (TaskStatus::where('name', 'Pendiente')->where('company_id', $company->id)->count() == 0) {
                TaskStatus::create([
                    'company_id' => $company->id,
                    'name' => 'Pendiente',
                    'weight' => 10,
                    'color' => '#181c32'
                ]);
            }

            if (TaskStatus::where('name', 'En proceso')->where('company_id', $company->id)->count() == 0) {
                TaskStatus::create([
                    'company_id' => $company->id,
                    'name' => 'En proceso',
                    'weight' => 30,
                    'color' => '#3699ff'
                ]);
            }

            if (TaskStatus::where('name', 'Pendiente información')->where('company_id', $company->id)->count() == 0) {
                TaskStatus::create([
                    'company_id' => $company->id,
                    'name' => 'Pendiente información',
                    'weight' => 20,
                    'color' => '#ffa800'
                ]);
            }

            if (TaskStatus::where('name', 'Validación')->where('company_id', $company->id)->count() == 0) {
                TaskStatus::create([
                    'company_id' => $company->id,
                    'name' => 'Validación',
                    'weight' => 70,
                    'color' => '#7337ee'
                ]);
            }

            if (TaskStatus::where('name', 'Finalizada')->where('company_id', $company->id)->count() == 0) {
                TaskStatus::create([
                    'company_id' => $company->id,
                    'name' => 'Finalizada',
                    'weight' => 90,
                    'color' => '#1bc5bd',
                    'finish' => 1
                ]);
            }

            if (TaskStatus::where('name', 'Cancelada')->where('company_id', $company->id)->count() == 0) {
                TaskStatus::create([
                    'company_id' => $company->id,
                    'name' => 'Cancelada',
                    'weight' => 5,
                    'color' => '#181c32',
                    'finish' => 1
                ]);
            }

            //RC: Añadimos el grupoç
            $company_config_group = CompanyConfigGroup::where('key', 'TASKS')->first();
            if (empty($company_config_group)) {
                $company_config_group = new CompanyConfigGroup();
                $company_config_group->key = 'TASKS';
                $company_config_group->name = 'Tareas';
                $company_config_group->save();
            }

            //RC: Añadimos las configuraciones de prioridad
            if (CompanyConfig::where(
                'company_id',
                $company->id
            )->where('key', 'default_task_priority')->count() == 0) {
                $company_config = new CompanyConfig();
                $company_config->company_id = $company->id;
                $company_config->company_config_group_id = $company_config_group->id;
                $company_config->key = 'default_task_priority';
                $company_config->label = 'Prioridad de la tarea por defecto';
                $company_config->position = 10;
                $company_config->value = '-';
                $company_config->save();
            }
            //RC: Añadimos las configuraciones de status
            if (CompanyConfig::where(
                'company_id',
                $company->id
            )->where('key', 'default_task_status')->count() == 0) {
                $company_config = new CompanyConfig();
                $company_config->company_id = $company->id;
                $company_config->company_config_group_id = $company_config_group->id;
                $company_config->key = 'default_task_status';
                $company_config->label = 'Estado de la tarea por defecto';
                $company_config->position = 20;
                $company_config->value = '-';
                $company_config->save();
            }
            //RC: Añadimos las configuraciones de tipos
            if (CompanyConfig::where(
                'company_id',
                $company->id
            )->where('key', 'default_task_type')->count() == 0) {
                $company_config = new CompanyConfig();
                $company_config->company_id = $company->id;
                $company_config->company_config_group_id = $company_config_group->id;
                $company_config->key = 'default_task_type';
                $company_config->label = 'Tipo de tarea por defecto';
                $company_config->position = 30;
                $company_config->value = '-';
                $company_config->save();
            }
        }
    }
}
