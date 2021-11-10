<?php

use Illuminate\Database\Seeder;

class NP00039ExtensionStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        App\ExtensionStatus::create(['id' => 1, 'name' => 'Extension eliminada Dialplan']);
        App\ExtensionStatus::create(['id' => 2, 'name' => 'Hint eliminada Dialplan']);
        App\ExtensionStatus::create(['id' => 3, 'name' => 'Disponible']);
        App\ExtensionStatus::create(['id' => 4, 'name' => 'En uso']);
        App\ExtensionStatus::create(['id' => 5, 'name' => 'Comunicado']);
        App\ExtensionStatus::create(['id' => 6, 'name' => 'No accesible']);
        App\ExtensionStatus::create(['id' => 7, 'name' => 'Sonando']);
        App\ExtensionStatus::create(['id' => 8, 'name' => 'Sonando y en uso']);
        App\ExtensionStatus::create(['id' => 9, 'name' => 'Retenida']);
        App\ExtensionStatus::create(['id' => 10, 'name' => 'Retenida y en uso']);
    }
}
