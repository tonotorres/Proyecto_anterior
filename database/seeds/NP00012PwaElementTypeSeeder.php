<?php

use Illuminate\Database\Seeder;

class NP00012PwaElementTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\PwaElementType::create([
            'id' => 1,
            'name' => 'Texto'
        ]);

        \App\PwaElementType::create([
            'id' => 2,
            'name' => 'Imagen'
        ]);

        \App\PwaElementType::create([
            'id' => 3,
            'name' => 'Video'
        ]);

        \App\PwaElementType::create([
            'id' => 4,
            'name' => 'Audio'
        ]);

        \App\PwaElementType::create([
            'id' => 5,
            'name' => 'Slide'
        ]);

        \App\PwaElementType::create([
            'id' => 6,
            'name' => 'Embed'
        ]);

        \App\PwaElementType::create([
            'id' => 7,
            'name' => 'Botones'
        ]);

        \App\PwaElementType::create([
            'id' => 8,
            'name' => 'Formulario'
        ]);

        \App\PwaElementType::create([
            'id' => 9,
            'name' => 'Aceptación de un contrato'
        ]);

        \App\PwaElementType::create([
            'id' => 10,
            'name' => 'Contenido dinámico'
        ]);
    }
}
