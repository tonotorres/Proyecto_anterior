<?php

use Illuminate\Database\Seeder;

class NP00032CallLogTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        App\CallLogType::create([
            'id' => 1,
            'name' => 'start'
        ]);

        App\CallLogType::create([
            'id' => 2,
            'name' => 'queue'
        ]);

        App\CallLogType::create([
            'id' => 3,
            'name' => 'ivr'
        ]);

        App\CallLogType::create([
            'id' => 4,
            'name' => 'ring_group'
        ]);

        App\CallLogType::create([
            'id' => 5,
            'name' => 'agent_called'
        ]);

        App\CallLogType::create([
            'id' => 6,
            'name' => 'agent_connect'
        ]);

        App\CallLogType::create([
            'id' => 7,
            'name' => 'transfer'
        ]);

        App\CallLogType::create([
            'id' => 8,
            'name' => 'pickup'
        ]);

        App\CallLogType::create([
            'id' => 9,
            'name' => 'voicemail'
        ]);

        App\CallLogType::create([
            'id' => 10,
            'name' => 'hangup'
        ]);

        App\CallLogType::create([
            'id' => 11,
            'name' => 'setcallerid'
        ]);

        App\CallLogType::create([
            'id' => 12,
            'name' => 'setcallend'
        ]);
    }
}
