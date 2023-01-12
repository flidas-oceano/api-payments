<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use zcrmsdk\oauth\ZohoOAuth;
use zcrmsdk\crm\setup\org\ZCRMOrganization;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\crud\ZCRMInventoryLineItem;
use zcrmsdk\crm\crud\ZCRMModule;
use zcrmsdk\crm\exception\ZCRMException;

class ZohoController extends Controller
{

    public $emi_owner;

    public function __construct()
    {
        // dd(gettype(Storage::path("zoho")));

        try {
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
        } catch (Exception $e) {
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

    //trae records en base a condiciones
    private function fetchRecords($module, $conditions, $log = false)
    {
        $answer = array();



        try {
            $moduleIns = ZCRMRestClient::getInstance()->getModuleInstance($module);  //To get module instance
            $response = $moduleIns->searchRecordsByCriteria($conditions);
            $records = $response->getData();  //To get response data

            $answer = $records;
        } catch (\Exception $e) {
            if ($log) {
                $this->log($e);
            }
        }

        return ($answer);
    }

    //actualiza un record, le pasas el id separado
    private function updateRecord($type, $data, $id, $workflow = true)
    {
        $answer = array();

        $answer['result'] = 'error';
        $answer['id'] = '';

        try {
            $zcrmRecordIns = ZCRMRecord::getInstance($type, $id);

            foreach ($data as $k => $v)
                $zcrmRecordIns->setFieldValue($k, $v);

            //workflow?
            if ($workflow)
                $apiResponse = $zcrmRecordIns->update();
            else
                $apiResponse = $zcrmRecordIns->update(array());

            if ($apiResponse->getCode() == 'SUCCESS') {
                $answer['result'] = 'ok';
                $answer['id'] = $id;
            }
        } catch (\Exception $e) {
            $this->log(print_r($e, true));
        }

        return ($answer);
    }

    public function createLead(Request $request)
    {

        $data = $request->all();

        dd($request);

        $leadData = $this->processLeadData($data);

        $leadIsDuplicate = $this->updateFetchDuplicateLeads($leadData['Email']);


        if ($leadIsDuplicate)
            $leadData['Lead_Duplicado'] = true;

        $newLead =  $this->createNewRecord('Leads', $leadData);

        return (json_encode($newLead));
    }

    private function processLeadData($data)
    {
        //hay contactos?
        if ($this->fetchRecordWithValue('Contacts', 'Email', $data["email"]) == "error") {
            $leadData['Es_Contacto'] = false;
        } else {
            $leadData['Es_Contacto'] = true;
        }

        $leadData['First_Name']         = $data["name"];
        $leadData['Last_Name']             = $data["surname"];
        $leadData['Phone']                 = $data["phone"];
        $leadData['Email']                 = $data["email"];
        $leadData['Lead_Status']        = "Contacto urgente";
        //$leadData['Leads_manual']         = "Compra rechazada";
        $leadData['Pais']                 = $data["country"];
        $leadData['*owner']             = $this->emi_owner;

        return $leadData;
    }

    private function updateFetchDuplicateLeads($mail)
    {
        //hay leads con ese mail?
        $searchBy              =  "((Email:equals:" . $mail . ")and(Lead_Status:equals:Contacto urgente))";
        $sameUserLeads       =  $this->fetchRecords('Leads', $searchBy); //<-- busca records para saber si el usuario ya intentó comprar anteriormente

        if (count($sameUserLeads) == 0) {
            //no encontró nada, entonces no tiene que actualizar y no hay duplicados
            return false;
        }

        //si llegó acá es porque hay > 0 leads con mismo mail
        //vamos a actualizar SÓLO UNO de ellos qe tenga lead_duplicado = false

        $leadK = -1;

        foreach ($sameUserLeads as $k => $s) {
            if (!$s->getFieldValue('Lead_Duplicado')) {
                $leadK = $k;
                break;
            }
        }

        //o sea, que uno de ellos tiene lead_duplicado = false -> lo actualizamos
        if ($leadK != -1)
            $this->updateRecord('Leads', array('Lead_Duplicado' => true), $sameUserLeads[$leadK]->getEntityId(), false);

        return true;
    }
}
