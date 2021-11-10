<?php

use App\CompanyConfig;
use App\CompanyConfigGroup;
use Illuminate\Database\Seeder;

class NP00044CompanyConfigAddMessageNoPhone extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $company_config_group = CompanyConfigGroup::create([
            'key' => 'MESSAGES',
            'name' => 'Mensajes'
        ]);

        CompanyConfig::create([
            'company_id' => 1,
            'company_config_group_id' => $company_config_group->id,
            'key' => 'message_no_phone',
            'label' => 'Mensaje de teléfono no disponible',
            'position' => 10,
            'value' => 'No es posible conectar con tu extensión, por favor valida el teléfono o ponte en contacto con soporte.'
        ]);
    }
}
