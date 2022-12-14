<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use zcrmsdk\oauth\ZohoOAuth;

class ZohoController extends Controller
{

    public static function initialize()
    {
        ZCRMRestClient::initialize([
            "client_id" => env('ZOHO_API_PAYMENTS_TEST_CLIENT_ID'),
            "client_secret" => env('ZOHO_API_PAYMENTS_TEST_CLIENT_SECRECT'),
            "redirect_uri" => 'https://www.zoho.com',
            "token_persistence_path" => Storage::path("zoho"),
            "persistence_handler_class" => "ZohoOAuthPersistenceByFile",
            "currentUserEmail" => 'copyzoho.custom@gmail.com',
            "accounts_url" => 'https://accounts.zoho.com',
            "access_type" => "offline"
        ]);

       $oAuthClient = ZohoOAuth::getClientInstance();
        $refreshToken = "1000.89ade64aa0e71969aa029ae5c9fa6d83.d26f4542c39df303b08e54b4d8a5b26f";
        $userIdentifier = "copyzoho.custom@gmail.com";
        $oAuthTokens = $oAuthClient->generateAccessTokenFromRefreshToken($refreshToken, $userIdentifier);
    }

    public function fetchRecordWithValue($module, $field, $value)
    {
        $answer = 'error';
        $record = null;
        try {
            $moduleIns = ZCRMRestClient::getInstance()->getModuleInstance($module);  //To get module instance
            $response = $moduleIns->searchRecordsByCriteria('(' . $field . ':equals:' . $value . ')');
            $records = $response->getData();  //To get response data
            $answer = $records[0];
        } catch (\Exception $e) {
            dump($e);
        }
        return ($answer);
    }

    public function getContractBySO(Request $request, $so)
    {
        self::initialize();
        $answer = 'error';

        $so = (int)$so;
        $record = null;

        try {
            $record = $this->fetchRecordWithValue('Sales_Orders', 'SO_Number', $so);
            if ($record != 'error') {
                $answer = $record;
            } else
                $answer = '???';
        } catch (\Exception $e) {
            dump($e);
        }

        return ($answer);
    }
}
