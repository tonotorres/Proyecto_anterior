<?php

use Illuminate\Database\Seeder;

class NP00006ContactTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\ContactType::create([
            'id' => 1,
            'name' => 'Teléfono'
        ]);

        \App\ContactType::create([
            'id' => 2,
            'name' => 'Email'
        ]);

        \App\ContactTypeService::create([
            'id' => 1,
            'contact_type_id' => 1,
            'name' => 'Llamadas'
        ]);

        \App\ContactTypeService::create([
            'id' => 2,
            'contact_type_id' => 1,
            'name' => 'SMS'
        ]);

        \App\ContactTypeService::create([
            'id' => 3,
            'contact_type_id' => 1,
            'name' => 'Whatsapp'
        ]);
    }
}
