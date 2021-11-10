<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(NP00000FieldTypeSeeder::class);
        $this->call(NP00001CompaniesSeeder::class);
        $this->call(NP00002UserTemplatesSeeder::class);
        $this->call(NP00003UserTypesSeeder::class);
        $this->call(NP00004UsersSeeder::class);
        $this->call(NP00005AccountTypeSeeder::class);
        $this->call(NP00006ContactTypeSeeder::class);
        $this->call(NP00007ContactSeeder::class);
        $this->call(NP00008ContactContactTypeSeeder::class);
        $this->call(NP00009AccountsSeeder::class);
        $this->call(NP00010AccountContactTypeSeeder::class);
        $this->call(NP00011PwaPageSeeder::class);
        $this->call(NP00012PwaElementTypeSeeder::class);
        $this->call(NP00013PwaLanguageSeeder::class);
        $this->call(NP00014PwaElementSeeder::class);
        $this->call(NP00015DepartmentSeeder::class);
        $this->call(NP00016MessageSeeder::class);
        $this->call(NP00017LanguageSeeder::class);
        $this->call(NP00018TextSeeder::class);
        $this->call(NP00019CountrySeeder::class);
        $this->call(NP00020RegionSeeder::class);
        $this->call(NP00021AccountAddressSeeder::class);
        $this->call(NP00023TaxGroupSeeder::class);
        $this->call(NP00024TaxSeeder::class);
        $this->call(NP00025AddressBookSeeder::class);
        $this->call(NP00026AddressBookDestinationSeeder::class);
        $this->call(NP00027AddressBookOptionSeeder::class);
        $this->call(NP00028BreakTimesSeeder::class);
        $this->call(NP00029CallTypeSeeder::class);
        $this->call(NP00030CallStatusSeeder::class);
        $this->call(NP00031CallEndSeeder::class);
        $this->call(NP00032CallLogTypeSeeder::class);
        $this->call(NP00033CallSeeder::class);
        $this->call(NP00034ExtensionSeeder::class);
        $this->call(NP00035RouteInSeeder::class);
        $this->call(NP00036RouteOutSeeder::class);
        $this->call(NP00037QueueSeeder::class);
        $this->call(NP00038TagSeeder::class);
        $this->call(NP00039ExtensionStatusSeeder::class);
        $this->call(NP00040MsDynamicsSeeder::class);
        $this->call(NP00041ReportSeeder::class);
        $this->call(NP00042ReportTypeSeeder::class);
        $this->call(NP00043ReportItemSeeder::class);
        $this->call(NP00044CompanyConfigAddMessageNoPhone::class);
        $this->call(NP00045IvrSeeder::class);
        $this->call(NP00046IvrOptionSeeder::class);
        $this->call(NP00047WhatsappSeeder::class);
        $this->call(NP00048MessageTemplateSeeder::class);
        $this->call(NP00049ProjectPrioritySeeder::class);
        $this->call(NP00050ProjectStatusSeeder::class);
        $this->call(NP00051ProjectStageStatusSeeder::class);
        $this->call(NP00052ProjectSeeder::class);
        $this->call(NP00053ProjectCommentSeeder::class);
        $this->call(NP00054ProjectStageSeeder::class);
        $this->call(NP00055TaskPrioritySeeder::class);
        $this->call(NP00056TaskStatusSeeder::class);
        $this->call(NP00057TaskTypeSeeder::class);
        $this->call(NP00058TaskListSeeder::class);
        $this->call(NP00059TaskSeeder::class);
        $this->call(NP00060TaskCommentSeeder::class);
        $this->call(NP00061UserTemplateModuleSeeder::class);
        $this->call(NP00062CallConfigSeeder::class);
        $this->call(NP00063CampaignSeeder::class);
        $this->call(NP00064FormSeeder::class);
        $this->call(NP00065CampaignFormInputSeeder::class);
        $this->call(NP00066CampaignAnswerEndSeeder::class);
        $this->call(NP00067CampaignContactSeeder::class);
        $this->call(NP00068CampaignCallSeeder::class);
    }
}
