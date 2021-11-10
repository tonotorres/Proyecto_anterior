<?php

use App\Company;
use App\CompanyConfig;
use App\CompanyConfigGroup;
use App\Department;
use App\DepartmentConfig;
use App\DepartmentConfigGroup;
use Illuminate\Database\Seeder;

class NP00047WhatsappSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $companies = Company::all();
        $departments = Department::all();

        $config_group = CompanyConfigGroup::where('key', 'WHATSAPP')
            ->first();

        if(empty($config_group)) {
            $config_group = new CompanyConfigGroup();
            $config_group->key = 'WHATSAPP';
            $config_group->name = 'Whatsapp';
            $config_group->save();
        }

        foreach($companies as $company) {
            $config = CompanyConfig::where('company_config_group_id', $config_group->id)
                ->where('company_id', $company->id)
                ->where('key', 'whatsapp_numbers')
                ->first();

            if(empty($config)) {
                $config = new CompanyConfig();
                $config->company_id = $company->id;
                $config->company_config_group_id = $config_group->id;
                $config->key = "whatsapp_numbers";
                $config->label = "Número de whatsapp separados por comas";
                $config->position = 10;
                $config->value = '';
                $config->save();
            }

            $config = CompanyConfig::where('company_config_group_id', $config_group->id)
                ->where('company_id', $company->id)
                ->where('key', 'whatsapp_channels')
                ->first();

            if(empty($config)) {
                $config = new CompanyConfig();
                $config->company_id = $company->id;
                $config->company_config_group_id = $config_group->id;
                $config->key = "whatsapp_channels";
                $config->label = "Canales de whatsapp separados por comas";
                $config->position = 20;
                $config->value = '';
                $config->save();
            }

            $config = CompanyConfig::where('company_config_group_id', $config_group->id)
                ->where('company_id', $company->id)
                ->where('key', 'whatsapp_accesskey')
                ->first();

            if(empty($config)) {
                $config = new CompanyConfig();
                $config->company_id = $company->id;
                $config->company_config_group_id = $config_group->id;
                $config->key = "whatsapp_accesskey";
                $config->label = "Accesskey de whatsapp separados por comas";
                $config->position = 30;
                $config->value = '';
                $config->save();
            }
        }

        $config_group = DepartmentConfigGroup::where('key', 'WHATSAPP')
            ->first();

        if(empty($config_group)) {
            $config_group = new DepartmentConfigGroup();
            $config_group->key = 'WHATSAPP';
            $config_group->name = 'Whatsapp';
            $config_group->save();
        }

        foreach($departments as $department) {
            $config = DepartmentConfig::where('department_config_group_id', $config_group->id)
                ->where('department_id', $department->id)
                ->where('key', 'whatsapp_numbers')
                ->first();

            if(empty($config)) {
                $config = new DepartmentConfig();
                $config->department_id = $department->id;
                $config->department_config_group_id = $config_group->id;
                $config->key = "whatsapp_numbers";
                $config->label = "Número de whatsapp separados por comas";
                $config->position = 10;
                $config->value = '';
                $config->save();
            }

            $config = DepartmentConfig::where('department_config_group_id', $config_group->id)
                ->where('department_id', $department->id)
                ->where('key', 'whatsapp_channels')
                ->first();

            if(empty($config)) {
                $config = new DepartmentConfig();
                $config->department_id = $department->id;
                $config->department_config_group_id = $config_group->id;
                $config->key = "whatsapp_channels";
                $config->label = "Canales de whatsapp separados por comas";
                $config->position = 20;
                $config->value = '';
                $config->save();
            }

            $config = DepartmentConfig::where('department_config_group_id', $config_group->id)
                ->where('department_id', $department->id)
                ->where('key', 'whatsapp_accesskey')
                ->first();

            if(empty($config)) {
                $config = new DepartmentConfig();
                $config->department_id = $department->id;
                $config->department_config_group_id = $config_group->id;
                $config->key = "whatsapp_accesskey";
                $config->label = "Accesskey de whatsapp separados por comas";
                $config->position = 30;
                $config->value = '';
                $config->save();
            }
        }
    }
}
