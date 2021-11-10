<?php

use App\Company;
use App\CompanyConfig;
use App\CompanyConfigGroup;
use Illuminate\Database\Seeder;

class NP00062CallConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $company_config_group = CompanyConfigGroup::where('key', 'CALL')->first();
            if (empty($company_config_group)) {
                $company_config_group = CompanyConfigGroup::create([
                    'key' => 'CALL',
                    'name' => 'Llamadas'
                ]);
            }

            if (CompanyConfig::where('key', 'openinternalcalls')->where('company_id', $company->id)->count() == 0) {
                CompanyConfig::create([
                    'company_id' => $company->id,
                    'company_config_group_id' => $company_config_group->id,
                    'key' => 'openinternalcalls',
                    'label' => 'Abrir ficha para las llamadas internas',
                    'position' => 10,
                    'value' => 0
                ]);
            }

            if (CompanyConfig::where('key', 'closecallswithoudendcall')->where('company_id', $company->id)->count() == 0) {
                CompanyConfig::create([
                    'company_id' => $company->id,
                    'company_config_group_id' => $company_config_group->id,
                    'key' => 'closecallswithoudendcall',
                    'label' => 'Permitir cerrar llamadas sin marcar un final',
                    'position' => 20,
                    'value' => 0
                ]);
            }
        }
    }
}
