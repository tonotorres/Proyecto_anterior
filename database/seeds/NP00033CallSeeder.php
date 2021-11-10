<?php

use Illuminate\Database\Seeder;

class NP00033CallSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $module_key = 30;

        //RC: Generamos el registro del módulo
        \App\Module::create([
            'key' => $module_key,
            'name' => 'Llamadas',
            'url' => 'calls',
            'help' => 'help/calls'
        ]);
    }
}
