<?php

use App\CallType;
use Illuminate\Database\Seeder;

class NP00029CallTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        CallType::create([
            'id' => 1,
            'name' => 'Entrada'
        ]);

        CallType::create([
            'id' => 2,
            'name' => 'Salida'
        ]);

        CallType::create([
            'id' => 3,
            'name' => 'Interna'
        ]);
    }
}
