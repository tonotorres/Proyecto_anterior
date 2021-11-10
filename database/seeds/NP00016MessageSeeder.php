<?php

use Illuminate\Database\Seeder;

class NP00016MessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $module_key = 14;

        //RC: Generamos el registro del módulo
        if( \App\Module::where('key', $module_key)->count() == 0) {
            \App\Module::create([
                'key' => $module_key,
                'name' => 'Chat',
                'url' => 'chats',
                'help' => 'help/chats'
            ]);
        }

        $module_key = 15;

        //RC: Generamos el registro del módulo
        if( \App\Module::where('key', $module_key)->count() == 0) {
            \App\Module::create([
                'key' => $module_key,
                'name' => 'Whatsapp',
                'url' => 'whatsapps',
                'help' => 'help/whatsapps'
            ]);
        }

        $module_key = 16;

        //RC: Generamos el registro del módulo
        if( \App\Module::where('key', $module_key)->count() == 0) {
            \App\Module::create([
                'key' => $module_key,
                'name' => 'SMS',
                'url' => 'sms',
                'help' => 'help/sms'
            ]);
        }

        if( \App\MessageType::where('id', 1)->count() == 0) {
            App\MessageType::create([
                'id' => 1,
                'name' => 'Chat'
            ]);
        }

        if( \App\MessageType::where('id', 2)->count() == 0) {
            App\MessageType::create([
                'id' => 2,
                'name' => 'Whatsapp'
            ]);
        }

        if( \App\MessageType::where('id', 3)->count() == 0) {
            App\MessageType::create([
                'id' => 3,
                'name' => 'SMS'
            ]);
        }

        if( \App\MessageBodyType::where('id', 1)->count() == 0) {
            App\MessageBodyType::create([
                'id' => 1,
                'name' => 'Texto'
            ]);
        }

        if( \App\MessageBodyType::where('id', 2)->count() == 0) {
            App\MessageBodyType::create([
                'id' => 2,
                'name' => 'Imagen'
            ]);
        }

        if( \App\MessageBodyType::where('id', 3)->count() == 0) {
            App\MessageBodyType::create([
                'id' => 3,
                'name' => 'Fichero'
            ]);
        }

        if( \App\MessageBodyType::where('id', 4)->count() == 0) {
            App\MessageBodyType::create([
                'id' => 4,
                'name' => 'Ubicación'
            ]);
        }

        if( \App\MessageBodyType::where('id', 5)->count() == 0) {
            App\MessageBodyType::create([
                'id' => 5,
                'name' => 'Audio'
            ]);
        }

        if( \App\MessageBodyType::where('id', 6)->count() == 0) {
            App\MessageBodyType::create([
                'id' => 6,
                'name' => 'Vídeo'
            ]);
        }
    }
}
