<?php

namespace App\Console\Commands;

use App\CompanyConfig;
use Illuminate\Console\Command;

class MsDynamicsGetToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'msdynamicsgettoken';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Obtenemos el token de microsoft dynamics';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $serviceUrlReg = CompanyConfig::where('key', 'msdynamics_token_service_url')->first();
        $clientIdReg = CompanyConfig::where('key', 'msdynamics_token_client_id')->first();
        $userNameReg = CompanyConfig::where('key', 'msdynamics_token_username')->first();
        $passwordReg = CompanyConfig::where('key', 'msdynamics_token_password')->first();
        $resourceReg = CompanyConfig::where('key', 'msdynamics_token_resource')->first();

        $serviceUrl = $serviceUrlReg->value;
        $clientId  = $clientIdReg->value;
        $userName = $userNameReg->value;
        $password = $passwordReg->value;
        $resource = $resourceReg->value;

        $post_fields["grant_type"] = "password";
        $post_fields["client_id"] = $clientId;
        $post_fields["username"] = $userName;
        $post_fields["password"] = $password;
        $post_fields["resource"] = $resource;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $serviceUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => $post_fields,
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $res = json_decode($response);
            CompanyConfig::where('key', 'msdynamics_api_token')->update(['value' => $res->access_token]);
        }
    }
}
