<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use zcrmsdk\crm\crud\ZCRMInventoryLineItem;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use zcrmsdk\oauth\ZohoOAuth;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\exception\ZCRMException;
use App\Models\{Contact, Lead, Profession, PurchaseProgress, Speciality, MethodContact};
use Illuminate\Support\Facades\Log;

class CronosController extends Controller
{
    public $emi_owner;

    public function __construct()
    {
        try {
            $this->emi_owner = '2712674000000899001';


            ZCRMRestClient::initialize([
                "client_id" => env('ZOHO_API_PAYMENTS_MSK_CLIENT_ID'),
                "client_secret" => env('ZOHO_API_PAYMENTS_MSK_CLIENT_SECRECT'),
                "redirect_uri" => 'https://www.msklatam.com',
                "token_persistence_path" => Storage::path("zohomsk"),
                "persistence_handler_class" => "ZohoOAuthPersistenceByFile",
                "currentUserEmail" => 'integraciones@msklatam.com',
                "accounts_url" => 'https://accounts.zoho.com',
                "access_type" => "offline"
            ]);

            $oAuthClient = ZohoOAuth::getClientInstance();
           $refreshToken = env('ZOHO_API_PAYMENTS_MSK_REFRESH_TOKEN');
           $userIdentifier = 'integraciones@msklatam.com';
           $oAuthTokens = $oAuthClient->generateAccessTokenFromRefreshToken($refreshToken, $userIdentifier);
       }catch(Exception $e){
        Log::error($e);

        }
    }

    public function index(){

        //Contacs,Sales_Orders,
        $answer = array();
        try {
            $module = ZCRMRestClient::getInstance()->getModuleInstance("Sales_Orders");
            $response = $module->getRecords(array(
                "page" => 1,
                "per_page" => 20
            ));

            $records = $response->getData();
            dd($records);
            /* collect($modules)->map(function($m) {
               dump($m);
            }); */
        } catch (\Exception $e) {
                Log::error($e);
                dd($e);
        }

        return ($answer);
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
        } catch (\zcrmsdk\crm\exception\ZCRMException $e) {
            Log::error($e);
        }
        return ($answer);
    }
}
