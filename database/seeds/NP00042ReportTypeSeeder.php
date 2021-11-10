<?php

use App\ReportType;
use Illuminate\Database\Seeder;

class NP00042ReportTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ReportType::create([
            'name' => 'Total de llamadas por tipología',
            'function' => 'total_calls_type'
        ]);

        ReportType::create([
            'name' => 'Duración de llamadas por tipología',
            'function' => 'duration_calls_type'
        ]);

        ReportType::create([
            'name' => 'Duración media de llamadas por tipología',
            'function' => 'media_calls_type'
        ]);

        ReportType::create([
            'name' => 'Llamadas por horas',
            'function' => 'total_calls_per_hour'
        ]);

        ReportType::create([
            'name' => 'Total de llamadas por estado',
            'function' => 'total_calls_call_status'
        ]);

        ReportType::create([
            'name' => 'Tiempo de espera',
            'function' => 'total_wait_calls'
        ]);

        ReportType::create([
            'name' => 'Tiempo de llamada',
            'function' => 'total_duration_calls'
        ]);

        ReportType::create([
            'name' => 'Total de llamadas por final',
            'function' => 'total_calls_call_end'
        ]);

        ReportType::create([
            'name' => 'Duración de llamadas por final',
            'function' => 'duration_calls_call_end'
        ]);

        ReportType::create([
            'name' => 'Duración media de llamadas por final',
            'function' => 'media_calls_call_end'
        ]);

        ReportType::create([
            'name' => 'Total de llamadas por final y usuario',
            'function' => 'total_calls_call_end_and_user'
        ]);
    }
}
