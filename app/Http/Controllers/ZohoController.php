<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use zcrmsdk\oauth\ZohoOAuth;

class ZohoController extends Controller
{

    public function __construct()
    {
       // dd(gettype(Storage::path("zoho")));

       try{
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

          /*  $oAuthClient = ZohoOAuth::getClientInstance();
           $refreshToken = "1000.9a6e53ae8b40e27e7c5d092c66a19b8d.45fc664d39ebd3e75c2b9672fc212d2a";
           $userIdentifier = "copyzoho.custom@gmail.com";
           $oAuthTokens = $oAuthClient->generateAccessTokenFromRefreshToken($refreshToken, $userIdentifier); */
       }catch(Exception $e){
            dd($e);
       }

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

        return response()->json($answer);
    }

    public function createLead(){
        $req = request()->validate([
            'nombre' => 'required|max:191',
            'apellido' => 'required|max:191',
            'email' => 'required|email|max:191',
            'area' => 'required|min:2|max:5',
            'telefono' => 'required|min:2|max:14',
            'direccion' => 'required|max:191',
            'pais' => 'required|max:80|min:1'
        ]);
        
       
        return response()->json($req);
    }
}
