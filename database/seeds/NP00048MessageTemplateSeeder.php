<?php

use Illuminate\Database\Seeder;

class NP00048MessageTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $module_key = 40;

        //RC: Generamos el registro del módulo
        if( \App\Module::where('key', $module_key)->count() == 0) {
            \App\Module::create([
                'key' => $module_key,
                'name' => 'Plantillas de mensaje',
                'url' => 'message_templates',
                'help' => 'help/message_templates'
            ]);
        }
    }
}
