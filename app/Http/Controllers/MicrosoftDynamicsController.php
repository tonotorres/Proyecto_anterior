<?php

namespace App\Http\Controllers;

use App\CompanyConfig;
use App\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MicrosoftDynamicsController extends Controller
{
    public function search_account_by_phone() {
        $phone = '666914350';
        $urls = ms_dynamics_generate_account_links($phone);
        dd($urls);
    }

    public function get_token() {
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

        exit;
    }

    /**
     * @deprecated
     */
    public function get_token_step2(Request $request) {
        $data = $request->all();

        $filename = 'ms_dynamics/get_token.txt';
        Storage::append($filename, json_encode($data));
        exit;

        $serviceUrl = "https://login.microsoftonline.com/common/oauth2/token";
        $clientId  = "58ad11d7-d732-4183-a512-a564e64493e1";
        $userName = "cestorach@pronokalgroup.com";
        $password = "Carles@1234#";

        $post_fields["grant_type"] = "authorization_code";
        $post_fields["client_id"] = "58ad11d7-d732-4183-a512-a564e64493e1";
        $post_fields["code"] = "AQABAAIAAACQN9QBRU3jT6bcBQLZNUj7kp7kB3pjkIK7IZ5DSu_2l-uKILdnB9Q04JLFreoutIdsTzGs1IFLoxZyFOTkSh6fhEn2n-9MyJTxWc-veOX7bK0NXUVNLJNkl1mOLXyVfvNEovpjzoTG_WUKTw6CvGnWTFeN0zcQ81LQkPaZa-gMdB2facztE-1lYPTWLFtKwWEV9v69GeL80WBzJx8vHyI3YzXjV-PFCtcW5LUAAHOb5ZL5dzNdXDvVeSuMDsXnHiob5Vog1IYlE1RNdMZJtoUExGEEKkUEbz192VegTXSRG8RfESEfwZ3vn3rwRzyXw26ev517hs2UcFWZYl18vZV1f49wL8K3HJjDZq3DN23-N1jgt0z58JKliT2hUVnNBmgEl83tWuSAVa5mIOSa4KCKtj1BLm_6skL3hI9AxxSEuU9JMbf_qKYd5vVBySTavu7sMC2GQkAFNq5biHpiHt0HlnDq2Iy1jfqT1DDAawD7srZl-MbrHNfKE4cW8zEnSVR9AWz_VucyDDGNs1K6_Wybkhm9mvXZbKg5c1R6INFFmVvbrIN50QFH1dG6CeQCi-kmiO9wF4bH80NOFgrc2_7DExb6sR3SqTWRWpcxlltRuSAA";
        $post_fields["client_secret"] = "18=K.GaUTYoC4Sf0:qp=?JktmI.sel99";

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
            echo $response;exit;
        }
    }
}