<?php

use App\CompanyConfig;
use App\CompanyConfigGroup;
use Illuminate\Database\Seeder;

class NP00040MsDynamicsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $company_config_group = CompanyConfigGroup::create([
            'key' => 'MSDYNAMICS',
            'name' => 'Microsoft Dynamics'
        ]);

        CompanyConfig::create([
            'company_id' => 1,
            'company_config_group_id' => $company_config_group->id,
            'key' => 'msdynamics_enable',
            'label' => 'Microsoft Dynamics Activado',
            'position' => 10,
            'value' => '0'
        ]);

        CompanyConfig::create([
            'company_id' => 1,
            'company_config_group_id' => $company_config_group->id,
            'key' => 'msdynamics_token_service_url',
            'label' => 'Microsoft token service url',
            'position' => 20,
            'value' => ''
        ]);

        CompanyConfig::create([
            'company_id' => 1,
            'company_config_group_id' => $company_config_group->id,
            'key' => 'msdynamics_token_client_id',
            'label' => 'Microsoft token client id',
            'position' => 30,
            'value' => ''
        ]);

        CompanyConfig::create([
            'company_id' => 1,
            'company_config_group_id' => $company_config_group->id,
            'key' => 'msdynamics_token_username',
            'label' => 'Microsoft token username',
            'position' => 40,
            'value' => ''
        ]);

        CompanyConfig::create([
            'company_id' => 1,
            'company_config_group_id' => $company_config_group->id,
            'key' => 'msdynamics_token_password',
            'label' => 'Microsoft token password',
            'position' => 50,
            'value' => ''
        ]);

        CompanyConfig::create([
            'company_id' => 1,
            'company_config_group_id' => $company_config_group->id,
            'key' => 'msdynamics_token_resource',
            'label' => 'Microsoft token resource',
            'position' => 60,
            'value' => ''
        ]);

        CompanyConfig::create([
            'company_id' => 1,
            'company_config_group_id' => $company_config_group->id,
            'key' => 'msdynamics_api_resource',
            'label' => 'Microsoft api resource',
            'position' => 70,
            'value' => ''
        ]);

        CompanyConfig::create([
            'company_id' => 1,
            'company_config_group_id' => $company_config_group->id,
            'key' => 'msdynamics_api_token',
            'label' => 'Microsoft api token',
            'position' => 80,
            'value' => ''
        ]);

        CompanyConfig::create([
            'company_id' => 1,
            'company_config_group_id' => $company_config_group->id,
            'key' => 'msdynamics_api_account_url',
            'label' => 'Microsoft api account url',
            'position' => 90,
            'value' => ''
        ]);
    }
}
