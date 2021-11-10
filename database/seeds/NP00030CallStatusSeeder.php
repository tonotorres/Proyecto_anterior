<?php

use App\CallStatus;
use Illuminate\Database\Seeder;

class NP00030CallStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (CallStatus::where('id', 1)->count() == 0) {
            CallStatus::create([
                'id' => 1,
                'name' => 'En cola'
            ]);
        }

        if (CallStatus::where('id', 2)->count() == 0) {
            CallStatus::create([
                'id' => 2,
                'name' => 'Activa'
            ]);
        }

        if (CallStatus::where('id', 3)->count() == 0) {
            CallStatus::create([
                'id' => 3,
                'name' => 'Correcta'
            ]);
        }

        if (CallStatus::where('id', 4)->count() == 0) {
            CallStatus::create([
                'id' => 4,
                'name' => 'Fallida'
            ]);
        }

        if (CallStatus::where('id', 5)->count() == 0) {
            CallStatus::create([
                'id' => 5,
                'name' => 'Buzón'
            ]);
        }

        if (CallStatus::where('id', 6)->count() == 0) {
            CallStatus::create([
                'id' => 6,
                'name' => 'Aparcada'
            ]);
        }

        if (CallStatus::where('id', 7)->count() == 0) {
            CallStatus::create([
                'id' => 7,
                'name' => 'Retenida'
            ]);
        }
    }
}
