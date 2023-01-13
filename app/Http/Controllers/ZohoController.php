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
       try{
            $this->emi_owner = '2712674000000899001';

           ZCRMRestClient::initialize([
               "client_id" => env('APP_DEBUG') ? env('ZOHO_API_PAYMENTS_TEST_CLIENT_ID') : env('ZOHO_API_PAYMENTS_PROD_CLIENT_ID'),
               "client_secret" => env('APP_DEBUG') ? env('ZOHO_API_PAYMENTS_TEST_CLIENT_SECRECT') : env('ZOHO_API_PAYMENTS_PROD_CLIENT_SECRECT'),
               "redirect_uri" => env('APP_DEBUG') ? 'https://www.zoho.com' : 'https://www.oceanomedicina.com.ar',
               "token_persistence_path" => Storage::path("zoho"),
               "persistence_handler_class" => "ZohoOAuthPersistenceByFile",
               "currentUserEmail" => env('APP_DEBUG') ? 'copyzoho.custom@gmail.com' : 'sistemas@oceano.com.ar', //'copyzoho.custom@gmail.com',
               "accounts_url" => 'https://accounts.zoho.com',
               "access_type" => "offline"
           ]);

            $oAuthClient = ZohoOAuth::getClientInstance();
           $refreshToken = env('APP_DEBUG') ? env('ZOHO_API_PAYMENTS_TEST_REFRESH_TOKEN') : env('ZOHO_API_PAYMENTS_PROD_REFRESH_TOKEN');
           $userIdentifier = env('APP_DEBUG') ? 'copyzoho.custom@gmail.com' : 'sistemas@oceano.com.ar';
           $oAuthTokens = $oAuthClient->generateAccessTokenFromRefreshToken($refreshToken, $userIdentifier); 
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

 //crea un nuevo record, el que vos quieras, contacto, contrato...
    //pero momento! si ya existe no crea nada nuevo.
    //en cualquier caso, te devuelve el id
    //exception -> reventó todo
    //ok -> salio bien
    //duplicate -> no es malo, pero está duplicado, o sea que no se crea, sino que trae su id
    private function createNewRecord($type, $data)
    {
        $status = 'ok'; //el status, y en base a esto armo el answer o no...
        //ok = salio bien, y te paso el id
        //exception = exploto todo

        $answer = array();

        $answer['result'] = '';
        $answer['id'] = '';


        //hace el intento de subir el record
        try {
            $moduleIns = ZCRMRestClient::getInstance()->getModuleInstance($type); //to get the instance of the module

            $record = ZCRMRecord::getInstance($type, null);

            foreach ($data as $k => $v)
                $record->setFieldValue($k, $v);

            $responseIn = $record->create();
            $details = $responseIn->getDetails();

            $answer['result'] = 'ok';
            $answer['id'] = $details['id'];
        } catch (ZCRMException $e) {
            $handle = $this->handleError($e, $type, $data);

            if ($handle != 'error') {
                $answer['result'] = 'duplicate';
                $answer['id'] = $handle;
            } else {
                $answer['result'] = 'error';
                $this->log($e);
            }
        }

        return ($answer);
    }


    //gestiona un error de subida de record
    //error es el codigo y el mensaje
    //type para saber qué estaba subiendo
    //data que estaba subiendo
    //respuesta ok = te da un id, sino error
    private function handleError($error, $type, $data)
    {
        $answer = 'error';

        $details = $error->getExceptionDetails();
        $cod = $error->getExceptionCode();

        if ($cod == 'DUPLICATE_DATA') {
            $answer = $details['id'];
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

    public function updateZohoStripe(Request $request)
	{
		
		$post = $request->all();
		
		$answer = 1;
		
		$send = [];
		
		$send['mail'] = '';
		$send['amount'] = 0;
		$send['total'] = 0;
		$send['installments'] = 0;
		$send['sub_id'] = '';
		$send['contract_id'] = '';
		$send['is_suscri'] = 0;
		$send['fullname'] = '';
		$send['address'] = '';
		$send['dni'] = '';
		$send['phone'] = '';
		
		$needed = ['mail','amount','total','installments','sub_id','contract_id','is_suscri',
		'fullname','address','dni','phone'];
		
		//check needed values
		foreach($needed as $n)
			if (isset($post[$n]))
				$send[$n] = $post[$n];
			else
			{
				$answer = 2;
				break;
			}
			
		if($answer != 2)
		{ 
			$is_suscri = '';
	
			if($send['is_suscri'] == 'true')
				$is_suscri = true;
			else if($send['is_suscri'] == 'false')
				$is_suscri = false;
	
			$dataUpdate = [
				'Email'=> $send['mail'],
				'Monto_de_Anticipo'=> $send['amount'],
				'Monto_de_Saldo'=> $send['total'] - $send['amount'],
				'Cantidad'=> $send['installments'], //Nro de cuotas
				'Valor_Cuota'=> $send['amount'], //Costo de cada cuota
				'Cuotas_restantes_sin_anticipo'=> $send['installments'] - 1,
				'Fecha_de_Vto'=> date('Y-m-d'),
				'Status'=> 'Contrato Efectivo',
				'Modalidad_de_pago_del_Anticipo'=> 'Stripe',
				'Medio_de_Pago'=> 'Stripe',
				'Es_Suscri'=> $is_suscri,
				'stripe_subscription_id' => $send['sub_id'],
				'L_nea_nica_6' => $send['fullname'],
				'Billing_Street' => $send['address'],
				'L_nea_nica_3' => strval($send['dni']),
				'Tel_fono_Facturacion' => $send['phone']
			];
			
			$update = $this->updateRecord('Sales_Orders', $dataUpdate, $send['contract_id'],true);
			
			if($update['result'] == 'ok')
				$answer = 1;
			else
				$answer = 0;
		}
		
		if($answer == 0)
			$answer = ['msg' => 'could not update zoho', 'code' => $answer];
		else if($answer == 1)
			$answer = ['msg' => 'ok', 'code' => $answer];
		else if($answer == 2)
			$answer = ['msg' => 'missing data', 'code' => $answer];
		
		return response()->json($answer);
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
        $LeadHistoricoData['Fuente_de_Lead']  = array(0 => $data['lead_source']);
        $LeadHistoricoData['FUENTE']         = $data['source'];
        $leadData['Lead_Status']        = $data['status'];
        $leadData['Pais']                 = $data["country"];
        $leadData['pp']                 = $data["profession"];
        $leadData['Especialidad']       = $data["specialty"];
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
