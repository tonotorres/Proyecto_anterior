<?php

use Illuminate\Database\Seeder;

class NP00000FieldTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (\App\FieldType::where('id', 1)->count() == 0) {
            \App\FieldType::insert(
                [
                    'id' => 1,
                    'name' => 'Oculto',
                    'has_options' => false,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );
        }
        if (\App\FieldType::where('id', 2)->count() == 0) {
            \App\FieldType::insert(
            [
                'id' => 2,
                'name' => 'Texto',
                'has_options' => false,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
                ]
            );
        }
        if (\App\FieldType::where('id', 3)->count() == 0) {
            \App\FieldType::insert(
            [
                'id' => 3,
                'name' => 'Number',
                'has_options' => false,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
                ]
            );
        }
        if (\App\FieldType::where('id', 4)->count() == 0) {
            \App\FieldType::insert(
            [
                'id' => 4,
                'name' => 'Decimal2',
                'has_options' => false,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
                ]
            );
        }
        if (\App\FieldType::where('id', 5)->count() == 0) {
            \App\FieldType::insert(
            [
                'id' => 5,
                'name' => 'Decimal4',
                'has_options' => false,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
                ]
            );
        }
        if (\App\FieldType::where('id', 6)->count() == 0) {
            \App\FieldType::insert(
            [
                'id' => 6,
                'name' => 'Email',
                'has_options' => false,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
                ]
            );
        }
        if (\App\FieldType::where('id', 7)->count() == 0) {
            \App\FieldType::insert(
            [
                'id' => 7,
                'name' => 'Password',
                'has_options' => false,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
                ]
            );
        }
        if (\App\FieldType::where('id', 8)->count() == 0) {
            \App\FieldType::insert(
            [
                'id' => 8,
                'name' => 'Desplegable',
                'has_options' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
                ]
            );
        }
        if (\App\FieldType::where('id', 9)->count() == 0) {
            \App\FieldType::insert(
            [
                'id' => 9,
                'name' => 'Desplegable Multi-selección',
                'has_options' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
                ]
            );
        }
        if (\App\FieldType::where('id', 10)->count() == 0) {
            \App\FieldType::insert(
            [
                'id' => 10,
                'name' => 'Texto largo',
                'has_options' => false,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
                ]
            );
        }
        if (\App\FieldType::where('id', 11)->count() == 0) {
            \App\FieldType::insert(
            [
                'id' => 11,
                'name' => 'Texto enriquecido',
                'has_options' => false,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
                ]
            );
        }
        if (\App\FieldType::where('id', 12)->count() == 0) {
            \App\FieldType::insert(
            [
                'id' => 12,
                'name' => 'Fichero',
                'has_options' => false,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
                ]
            );
        }
        if (\App\FieldType::where('id', 13)->count() == 0) {
            \App\FieldType::insert(
            [
                'id' => 13,
                'name' => 'Casillas de selección (radio)',
                'has_options' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
                ]
            );
        }
        if (\App\FieldType::where('id', 14)->count() == 0) {
            \App\FieldType::insert(
            [
                'id' => 14,
                'name' => 'Casillas de selección múltiples (checkbox)',
                'has_options' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
                ]
            );
        }
        if (\App\FieldType::where('id', 15)->count() == 0) {
            \App\FieldType::insert(
            [
                'id' => 15,
                'name' => 'Fecha',
                'has_options' => false,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
                ]
            );
        }
        if (\App\FieldType::where('id', 16)->count() == 0) {
            \App\FieldType::insert(
            [
                'id' => 16,
                'name' => 'Color',
                'has_options' => false,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
                ]
            );
        }
        if (\App\FieldType::where('id', 17)->count() == 0) {
            \App\FieldType::insert(
                [
                    'id' => 17,
                    'name' => 'Tiempo',
                    'has_options' => false,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
}
