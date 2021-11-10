<?php

namespace App\Console\Commands;

use App\Module;
use App\UserTemplate;
use App\UserTemplateField;
use App\UserTemplateModule;
use App\UserTemplateSection;
use App\UserTemplateTab;
use Illuminate\Console\Command;

class UpdateUserTemplateFields extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updateusertemplates {user_template_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza los campos de las plantillas con la información del núcleo';

    /**
     * The console command instance.
     *
     * @var \Illuminate\Console\Command
     */
    protected $command;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->command = new Command();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $user_template_id = $this->argument('user_template_id', null);

        //RC: Obtenemos todas las plantillas o la plantilla seleccionada.
        if (!empty($user_template_id)) {
            $user_templates[] = UserTemplate::findOrFail($user_template_id);
            UserTemplateModule::where('user_template_id', $user_template_id)->delete();
        } else {
            $user_templates = UserTemplate::all();
            UserTemplateModule::where('id', '>=', '1')->delete();
        }

        $modules = Module::all();

        foreach ($user_templates as $user_template) {
            $this->line("<info>Validamos la plantilla:</info>  $user_template->name");
            foreach ($modules as $module) {
                $this->line("<info>Validamos el módulo:</info>  $module->name");
                //RC: Para cada módulo miramos si existe i en caso contrario lo creamos
                if ($user_template->user_template_modules()->where('module_id', $module->id)->count() == 0) {
                    //RC: Solo queremos dar permisos crud para el superadministrador
                    if($user_template->id == 1) {
                        $crud = true;
                    } else {
                        $crud = false;
                    }

                    $user_template_module = new UserTemplateModule();
                    $user_template_module->user_template_id = $user_template->id;
                    $user_template_module->module_id = $module->id;
                    $user_template_module->name = $module->name;
                    $user_template_module->create = $crud;
                    $user_template_module->read = $crud;
                    $user_template_module->update = $crud;
                    $user_template_module->delete = $crud;
                    $user_template_module->list = $crud;
                    $user_template_module->own = $crud;
                    $user_template_module->save();
                    $this->line("<comment>Creamos el módulo:</comment> $module->name para la plantilla <comment>$user_template->name</comment>");
                } else {
                    //RC: Obtenemos el módulo si ya está generado
                    $user_template_module = $user_template->user_template_modules()->where('module_id', $module->id)->first();
                }

                //RC: Miramos pestaña por pestaña para ver que esté todo incluido
                foreach ($module->tabs as $tab) {
                    if ($user_template_module->user_template_tabs()->where('tab_id', $tab->id)->count() == 0) {
                        $this->line("<comment>Creamos la pestaña:</comment> $tab->name para la plantilla <comment>$user_template->name / $module->name</comment>");
                        $user_template_tab = new UserTemplateTab();
                        $user_template_tab->user_template_module_id = $user_template_module->id;
                        $user_template_tab->tab_id = $tab->id;
                        $user_template_tab->name = $tab->name;
                        $user_template_tab->position = $tab->position;
                        $user_template_tab->save();
                    } else {
                        $user_template_tab = $user_template_module->user_template_tabs()->where('tab_id', $tab->id)->first();
                    }

                    foreach ($tab->sections as $section) {
                        if ($user_template_tab->user_template_sections()->where('section_id', $section->id)->count() == 0) {
                            $this->line("<comment>Creamos la sección:</comment> $section->name para la plantilla <comment>$user_template->name / $module->name / $tab->name</comment>");
                            $user_template_section = new UserTemplateSection();
                            $user_template_section->user_template_tab_id = $user_template_tab->id;
                            $user_template_section->section_id = $section->id;
                            $user_template_section->name = $section->name;
                            $user_template_section->position = $section->position;
                            $user_template_section->save();
                        } else {
                            $user_template_section = $user_template_tab->user_template_sections()->where('section_id', $section->id)->first();
                        }

                        foreach ($section->fields as $field) {
                            if ($user_template_section->user_template_fields()->where('field_id', $field->id)->count() == 0) {
                                $this->line("<comment>Creamos el campo:</comment> $field->name para la plantilla <comment>$user_template->name / $module->name / $tab->name / $section->name</comment>");
                                $user_template_field = new UserTemplateField();
                                $user_template_field->user_template_section_id = $user_template_section->id;
                                $user_template_field->field_id = $field->id;
                                $user_template_field->field_type_id = $field->field_type_id;
                                $user_template_field->key = $field->key;
                                $user_template_field->width = $field->width;
                                $user_template_field->label = $field->label;
                                $user_template_field->name = $field->name;
                                $user_template_field->default = $field->default;
                                $user_template_field->validations_create = $field->validations_create;
                                $user_template_field->validations_update = $field->validations_update;
                                $user_template_field->options = $field->options;
                                $user_template_field->position = $field->position;
                                $user_template_field->is_simple_form = $field->is_simple_form;
                                $user_template_field->save();
                            }
                        }
                    }
                }
            }
        }
    }
}
