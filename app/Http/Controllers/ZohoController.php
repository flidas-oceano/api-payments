<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use zcrmsdk\oauth\ZohoOAuth;
use zcrmsdk\crm\crud\ZCRMRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use zcrmsdk\crm\exception\ZCRMException;
use zcrmsdk\crm\setup\org\ZCRMOrganization;
use zcrmsdk\crm\crud\ZCRMInventoryLineItem;
use App\Http\Requests\UpdateContractZohoRequest;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;

use App\Models\{Contact, Lead, Profession, PurchaseProgress, Speciality, MethodContact, PlaceToPaySubscription, PlaceToPayTransaction, SourceLead};
use App\Services\PlaceToPay\PlaceToPayService;

class ZohoController extends Controller
{

    public $emi_owner;
    public $placeToPayService = null;

    public function reinit()
    {
        try {

            $this->emi_owner = 'x';

            ZCRMRestClient::initialize([
                "client_id" => '1000.3RG4V6380Z6J0QJ8VGXO2V0PBMELGK',
                "client_secret" => '81d8708344811e068588c0bf635a186f195da8bedb',
                "redirect_uri" => 'https://www.msklatam.com',
                "token_persistence_path" => Storage::path("zohomsk"),
                "persistence_handler_class" => "ZohoOAuthPersistenceByFile",
                "currentUserEmail" => 'integraciones@msklatam.com',
                "accounts_url" => 'https://accounts.zoho.com',
                "access_type" => "offline"
            ]);

            $oAuthClient = ZohoOAuth::getClientInstance();

            $refreshToken = "1000.21d634af0695ff7e2ea1c783628d3ead.a5ec4489bb6cdb31c8ac2f5435f94923";
            $userIdentifier = "integraciones@msklatam.com";
            $oAuthTokens = $oAuthClient->generateAccessTokenFromRefreshToken($refreshToken, $userIdentifier);
            //$this->token = $oAuthClient->getAccessToken('https://www.msklatam.com');

        } catch (Exception $e) {
            Log::error($e);

        }
    }

    public function __construct(PlaceToPayService $placeToPayService)
    {
        try {
            $this->placeToPayService = $placeToPayService;

            $this->emi_owner = 'x';

            ZCRMRestClient::initialize([
                "client_id" => env('ZOHO_CRM_MSK_PAYMENTS_CLIENT_ID'),
                "client_secret" => env('ZOHO_CRM_MSK_PAYMENTS_CLIENT_SECRECT'),
                "redirect_uri" => 'https://www.msklatam.com',
                "token_persistence_path" => Storage::path("zoho"),
                "persistence_handler_class" => "ZohoOAuthPersistenceByFile",
                "currentUserEmail" => 'integraciones@msklatam.com',
                "accounts_url" => 'https://accounts.zoho.com',
                "access_type" => "offline"
            ]);

            $oAuthClient = ZohoOAuth::getClientInstance();
            $refreshToken = env('ZOHO_CRM_MSK_PAYMENTS_REFRESH_TOKEN');
            $userIdentifier = 'integraciones@msklatam.com';
            $oAuthTokens = $oAuthClient->generateAccessTokenFromRefreshToken($refreshToken, $userIdentifier);
        } catch (Exception $e) {
            Log::error($e);

        }
    }

    public function fetchRecordWithValue($module, $field, $value)
    {
        $answer = 'error';
        $record = null;
        try {
            $moduleIns = ZCRMRestClient::getInstance()->getModuleInstance($module); //To get module instance
            $response = $moduleIns->searchRecordsByCriteria('(' . $field . ':equals:' . $value . ')');
            $records = $response->getData(); //To get response data
            $answer = $records[0];
        } catch (\zcrmsdk\crm\exception\ZCRMException $e) {
            Log::debug($e);
        }
        return ($answer);
    }

    public function getContractBySO(Request $request, $so)
    {
        $answer = 'error';

        $so = (int) $so;
        $record = null;

        try {
            $record = $this->fetchRecordWithValue('Sales_Orders', 'SO_Number', $so);
            if ($record != 'error') {
                $answer = $record;
            } else
                $answer = '???';
        } catch (\Exception $e) {
            Log::error($e);
        }

        return response()->json($answer);
    }

    //trae records en base a condiciones
    public function fetchRecords($module, $conditions, $log = false)
    {
        $answer = array();
        try {
            $moduleIns = ZCRMRestClient::getInstance()->getModuleInstance($module); //To get module instance
            $response = $moduleIns->searchRecordsByCriteria($conditions);
            $records = $response->getData(); //To get response data

            $answer = $records;
        } catch (\Exception $e) {
            if ($log) {
                Log::error($e);
            }
        }

        return ($answer);
    }

    //crea un nuevo record, el que vos quieras, contacto, contrato...
    //pero momento! si ya existe no crea nada nuevo.
    //en cualquier caso, te devuelve el id
    //exception -> reventó todo
    //ok -> salio bien
    //duplicate -> no es malo, pero esPlaceToPaySubscription tá duplicado, o sea que no se crea, sino que trae su id
    public function createNewRecord($type, $data)
    {
        $status = 'ok'; //el status, y en base a esto armo el answer o no...
        //ok = salio bien, y te paso el id
        //exception = exploto todo

        $answer = array();

        $answer['result'] = '';
        $answer['id'] = '';
        $answer['detail'] = '';


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
                $answer['detail'] = $e->getExceptionDetails();
                Log::error($e);
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
        // ID_ALREADY_CONVERTED

        return ($answer);
    }

    //actualiza un record, le pasas el id separado
    public function updateRecord($type, $data, $id, $workflow = true)
    {
        $answer = array();

        $answer['result'] = 'error';
        $answer['id'] = '';
        $answer['detail'] = '';

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
        } catch (ZCRMException $e) {
            Log::error($e);

            if (!empty($e->getExceptionDetails()))
                $answer['detail'] = $e->getExceptionDetails();
            else
                $answer['detail'] = $e->getMessage();
        }

        return ($answer);
    }
    //actualiza un record, le pasas el id separado
    public function updateRecordNewVersion($type, $data, $id, $workflow = true)
    {
        $answer = array();

        $answer['result'] = '';
        $answer['id'] = '';
        // $answer['detail'] = '';
        $answer['detail'] = 'error';

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
                $answer['result'] = 'Se actualizo la entidad.';
                $answer['id'] = $id;
                $answer['detail'] = 'ok';
            }
        } catch (ZCRMException $e) {
            Log::error($e);

            if (!empty($e->getExceptionDetails()))
                $answer['result'] = $e->getExceptionDetails();
            else
                $answer['result'] = $e->getMessage();
        }

        return ($answer);
    }



    public function getContactByContract($so)
    {

        $answer = 'error';

        $so = (int) $so;
        $record = null;

        $record = $this->fetchRecordWithValue('Sales_Orders', 'SO_Number', $so);
        try {
            if ($record != 'error') {
                $answer = $record;
            } else
                $answer = '???';
        } catch (\Exception $e) {
            Log::error($e);
        }

        return response()->json($answer);
    }


    public function updateZohoStripe(UpdateContractZohoRequest $request)
    {
        $identification = $this->getIdentification($request->dni, $request->country);

        $dataUpdate = [
            'Email' => $request->email,
            'Anticipo' => $request->installment_amount,
            'Saldo' => $request->amount - $request->installment_amount,
            'Cantidad' => $request->installments,
            //Nro de cuotas
            'Monto_de_cuotas_restantes' => $request->is_advanceSuscription ? $request->payPerMonthAdvance : $request->installment_amount,
            //Costo de cada cuota
            'Cuotas_restantes_sin_anticipo' => $request->installments - 1,
            'Fecha_de_Vto' => date('Y-m-d'),
            'Status' => 'Contrato Efectivo',
            'Modalidad_de_pago_del_Anticipo' => 'Stripe',
            'Medio_de_Pago' => 'Stripe',
            'Es_Suscri' => boolval($request->is_suscri),
            'Suscripcion_con_Parcialidad' => boolval($request->is_advanceSuscription),
            'stripe_subscription_id' => $request->subscriptionId,
            'L_nea_nica_6' => $request->fullname,
            'Billing_Street' => $request->address,
            'L_nea_nica_3' => $identification,
            'Tel_fono_Facturacion' => $request->phone,
            'Discount' => abs($request->adjustment)
        ];

        $updateContract = $this->updateRecord('Sales_Orders', $dataUpdate, $request->contractId, true);

        if ($updateContract['result'] == 'error')
            return response()->json($updateContract, 500);
        else
            return response()->json($updateContract);
    }

    private function mappingDataContract($request, $gateway)
    {
        if (boolval($request->is_suscri)) {
            $modoDePago = 'Cobro recurrente';
            if (boolval($request->is_advanceSuscription)) {
                $modoDePago = $modoDePago . ' con parcialidad';
            }
        } else {
            $modoDePago = 'Cobro total en un pago';
        }

        if ($gateway == 'CTC') {
            return [
                // Contrato
                'Monto_de_parcialidad' => $request->installment_amount,
                'Seleccione_total_de_pagos_recurrentes' => strval($request->installments),
                'Monto_de_cada_pago_restantes' => $request->is_advanceSuscription ? $request->payPerMonthAdvance : $request->installment_amount,
                'Cantidad_de_pagos_recurrentes_restantes' => strval($request->installments - 1),
                'Fecha_de_primer_cobro' => date('Y-m-d'),
                'Status' => 'Aprobado',
                'M_todo_de_pago' => $gateway,
                'Modo_de_pago' => $modoDePago,

                //campos CTC
                'folio_suscripcion' => $request->subscriptionId,
                'folio_pago' => $request->folio_pago,
            ];
        }

        if ($gateway == 'Placetopay') {

            $session = PlaceToPayTransaction::where(['requestId' => $request['requestId']])->first();
            if ($session == null) {
                return response()->json('No se encontro la session en la DB.', 500);
            }
            $subscription = $session->lastApprovedSubscription();
            if ($subscription == null) {
                return response()->json('No se encontraron subcripciones de cuota 1 pagadas en la DB.', 500);
            }

            $detailApprovedPayments = [
                'Fecha_Cobro' => date('Y-m-d', strtotime($subscription->date_to_pay)),
                'Num_de_orden_o_referencia_ext' => $session->reference,
                'Cobro_ID' => $subscription->reference,
                'Monto' => $subscription->total,
                'Numero_de_cobro' => $subscription->nro_quote,
                'Origen_Pago' => 'SPP',
            ];

            return [
                'Monto_de_parcialidad' => $session->first_installment,
                'Seleccione_total_de_pagos_recurrentes' => strval($session->quotes),
                'Monto_de_cada_pago_restantes' => $session->remaining_installments,
                'Cantidad_de_pagos_recurrentes_restantes' => strval($session->quotes - 1),
                'Fecha_de_primer_cobro' => date('Y-m-d', strtotime($subscription->date_to_pay)),
                'Status' => 'Aprobado',
                'M_todo_de_pago' => $gateway,
                'Modo_de_pago' => $modoDePago,
                'stripe_subscription_id' => $session->reference,
                'Paso_5_Detalle_pagos' => [$detailApprovedPayments]
                // 'session_subscription_requestId' => $session->requestId,
                // 'cuota_subscription_requestId' => $session->getFirstInstallmentPaid()->requestId,

            ];
        }

        return [
            'Monto_de_parcialidad' => $request->installment_amount,
            'Seleccione_total_de_pagos_recurrentes' => strval($request->installments),
            'Monto_de_cada_pago_restantes' => $request->is_advanceSuscription ? $request->payPerMonthAdvance : $request->installment_amount,
            'Cantidad_de_pagos_recurrentes_restantes' => strval($request->installments - 1),
            'Fecha_de_primer_cobro' => date('Y-m-d'),
            'Status' => 'Aprobado',
            'M_todo_de_pago' => $gateway,
            'Modo_de_pago' => $modoDePago,
            'stripe_subscription_id' => $request->subscriptionId,
        ];
    }

    private function mappingDataContact($request, $gateway = null)
    {
        if ($gateway == 'Placetopay') {
            $paymentData = PlaceToPayTransaction::getPaymentDataByRequestId($request['requestId']);

            return [
                'Identificacion' => $paymentData->document,
                'Tel_fono_de_facturaci_n' => $paymentData->mobile,
                'Raz_n_social' => PlaceToPayTransaction::getFullNameFromPaymentData($paymentData),
            ];
        }

        $identification = $this->getIdentification($request->dni, $request->country);

        return [
            'Identificacion' => ($request->dni ?? $identification),
            'Tel_fono_de_facturaci_n' => $request->phone,
            'Raz_n_social' => $request->fullname,
        ];
    }

    private function processResponse($contact, $contract)
    {
        if ($contract['result'] == 'error' || $contact['result'] == 'error') {
            return response()->json(["contract" => $contract, "contact" => $contact], 500);
        }

        return response()->json(["contract" => $contract, "contact" => $contact]);
    }

    public function updateZohoStripeMSK(UpdateContractZohoRequest $request)
    {
        $saleZoho = $this->fetchRecordWithValue('Sales_Orders', 'id', $request->contractId)->getData();
        $contactEntityId = $saleZoho['Contact_Name']->getEntityId();

        $dataUpdate = $this->mappingDataContract($request, 'Stripe');

        $dataUpdateContact = $this->mappingDataContact($request);

        $updateContract = $this->updateRecord('Sales_Orders', $dataUpdate, $request->contractId, true);
        $updateContact = $this->updateRecord('Contacts', $dataUpdateContact, $contactEntityId, true);

        $this->processResponse($updateContact, $updateContract);
    }

    public function updateZohoMPMSK(UpdateContractZohoRequest $request)
    {

        $saleZoho = $this->fetchRecordWithValue('Sales_Orders', 'id', $request->contractId)->getData();
        $contactEntityId = $saleZoho['Contact_Name']->getEntityId();


        $dataUpdate = $this->mappingDataContract($request, 'Mercado Pago');
        $dataUpdateContact = $this->mappingDataContact($request);

        $updateContract = $this->updateRecord('Sales_Orders', $dataUpdate, $request->contractId, true);
        $updateContact = $this->updateRecord('Contacts', $dataUpdateContact, $contactEntityId, true);


        $this->processResponse($updateContact, $updateContract);
    }

    public function updateZohoCTCMSK(UpdateContractZohoRequest $request)
    {

        $request->validate([
            'folio_pago' => 'required'
        ]);

        $saleZoho = $this->fetchRecordWithValue('Sales_Orders', 'id', $request->contractId)->getData();
        $contactEntityId = $saleZoho['Contact_Name']->getEntityId();

        $dataUpdate = $this->mappingDataContract($request, 'CTC');
        $dataUpdateContact = $this->mappingDataContact($request);

        $updateContract = $this->updateRecord('Sales_Orders', $dataUpdate, $request->contractId, true);
        $updateContact = $this->updateRecord('Contacts', $dataUpdateContact, $contactEntityId, true);

        $this->processResponse($updateContact, $updateContract);
    }

    public function updateZohoMP(UpdateContractZohoRequest $request)
    {
        $identification = $this->getIdentification($request->dni, $request->country);

        $dataUpdate = [
            'Email' => $request->email,
            'Anticipo' => $request->installment_amount,
            'Saldo' => $request->amount - $request->installment_amount,
            'Cantidad' => $request->installments,
            //Nro de cuotas
            'Monto_de_cuotas_restantes' => $request->is_advanceSuscription ? $request->payPerMonthAdvance : $request->installment_amount,
            //Costo de cada cuota
            'Cuotas_restantes_sin_anticipo' => $request->installments - 1,
            'DNI' => $request->dni,
            //RFC_Solo_MX
            'Fecha_de_Vto' => date('Y-m-d'),
            'Status' => 'Contrato Efectivo',
            'Modalidad_de_pago_del_Anticipo' => 'Mercado pago',
            'Medio_de_Pago' => 'Mercado pago',
            'Es_Suscri' => boolval($request->is_suscri),
            'Suscripcion_con_Parcialidad' => boolval($request->is_advanceSuscription),
            'mp_subscription_id' => $request->subscriptionId,
            'L_nea_nica_6' => $request->fullname,
            'Billing_Street' => $request->address,
            'L_nea_nica_3' => $identification,
            'Tel_fono_Facturacion' => $request->phone,
            'Discount' => abs($request->adjustment)

        ];

        $updateContract = $this->updateRecord('Sales_Orders', $dataUpdate, $request->contractId, true);

        if ($updateContract['result'] == 'error')
            return response()->json($updateContract, 500);
        else
            return response()->json($updateContract);
    }


    public function updateZohoCTC(UpdateContractZohoRequest $request)
    {

        $request->validate([
            'folio_pago' => 'required'
        ]);

        $identification = $request->dni;

        $dataUpdate = [
            'Email' => $request->email,
            'Anticipo' => $request->installment_amount,
            'Saldo' => $request->amount - $request->installment_amount,
            'Cantidad' => $request->installments,
            //Nro de cuotas
            'Monto_de_cuotas_restantes' => $request->is_advanceSuscription ? $request->payPerMonthAdvance : $request->installment_amount,
            //Costo de cada cuota
            'Cuotas_restantes_sin_anticipo' => $request->installments - 1,
            // 'DNI' => $request->dni, //este no estaba definido
            //RFC_Solo_MX
            'Fecha_de_Vto' => date('Y-m-d'),
            'Status' => 'Contrato Efectivo',
            'Modalidad_de_pago_del_Anticipo' => 'CTC',
            'Medio_de_Pago' => 'CTC',
            'Es_Suscri' => boolval($request->is_suscri),
            'Suscripcion_con_Parcialidad' => boolval($request->is_advanceSuscription),
            'L_nea_nica_6' => $request->fullname,
            'Billing_Street' => $request->address,
            'L_nea_nica_3' => $identification,
            'Tel_fono_Facturacion' => $request->phone,
            'Discount' => abs($request->adjustment),
            //datos de folio
            'folio_suscripcion' => $request->subscriptionId,
            'folio_pago' => $request->folio_pago
        ];

        $updateContract = $this->updateRecord('Sales_Orders', $dataUpdate, $request->contractId, true);

        if ($updateContract['result'] == 'error')
            return response()->json($updateContract, 500);
        else
            return response()->json($updateContract);
    }

    public function saveCardZohoCTC(Request $request)
    {
        $data = $request->only(['card', 'card_v']);
        $updateContract = $this->updateRecord('Sales_Orders', ['Numero_de_tarjeta' => $data['card'], 'Vencimiento_de_tarjeta' => $data['card_v']], $request->contractId, true);

        if ($updateContract['result'] == 'error') {
            return response()->json($updateContract, 500);
        }

        return response()->json($updateContract);
    }

    public function updateZohoPTPMSK(Request $request)
    {
        try {

            $saleZoho = $this->getContractZoho($request->contractId)->getData();

            $contactEntityId = $saleZoho['Contact_Name']->getEntityId();

            $dataUpdate = $this->mappingDataContract($request, 'Placetopay');
            $dataUpdateContact = $this->mappingDataContact($request, 'Placetopay');

            $updateContract = $this->updateRecord('Sales_Orders', $dataUpdate, $request->contractId, true);
            $updateContact = $this->updateRecord('Contacts', $dataUpdateContact, $contactEntityId, true);

            $this->processResponse($updateContact, $updateContract);

        } catch (\Exception $e) {
            $err = [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                // 'trace' => $e->getTraceAsString(),
            ];

            Log::error("Error en updateZohoPTPMSK: " . $e->getMessage() . "\n" . json_encode($err, JSON_PRETTY_PRINT));
            return response()->json([
                $err
            ]);
        }
    }


    public function updateZohoPTP(Request $request)
    {
        try {
            $session = PlaceToPayTransaction::where(['requestId' => $request['requestId']])->get()->first();
            if ($session == null) {
                return response()->json('No se encontro la session en la DB.', 500);
            }
            $subscription = $session->subscriptions()->where(['nro_quote' => 1])->get()->first();
            if ($subscription == null) {
                return response()->json('No se encontraron subcripciones de cuota 1 pagadas en la DB.', 500);
            }


            $resultTransaction = $this->placeToPayService->getByRequestId($session->requestId, $cron = false, $isSubscription = true);
            $resultSubscription = $this->placeToPayService->getByRequestId($subscription->requestId, $cron = false, $isSubscription = true);

            $dataUpdate = [
                //Contrato
                'Anticipo' => $session->first_installment,
                'Cantidad' => $session->quotes,
                'Monto_de_cuotas_restantes' => $session->remaining_installments,
                'Cuotas_restantes_sin_anticipo' => $session->isAdvancedSubscription() ? $session->quotes - 1 : null,
                'Fecha_de_Vto' => date('Y-m-d'),
                'Status' => 'Contrato Efectivo',
                'Medio_de_Pago' => 'Placetopay',
                'Modalidad_de_pago_del_Anticipo' => 'Placetopay',
                'Saldo' => $resultSubscription['request']['payment']['amount']['total'],
                'Es_Suscri' => $session->isSubscription(),
                'Suscripcion_con_Parcialidad' => $session->isAdvancedSubscription(),
                'Discount' => abs($request['adjustment']),
                //Contrato
                'DNI' => $resultSubscription['request']['payer']['document'],
                'L_nea_nica_3' => $resultSubscription['request']['payer']['document'],
                'Tel_fono_Facturacion' => $resultSubscription['request']['payer']['mobile'],
                'L_nea_nica_6' => $resultSubscription['request']['payer']['name'] . " " . $resultSubscription['request']['payer']['surname'],
                'Email' => $resultSubscription['request']['payer']['email'],
                'Billing_Street' => $request['street'],
            ];

            // return $dataUpdate;

            $updateContract = $this->updateRecordNewVersion('Sales_Orders', $dataUpdate, $request->contractId, true);
            if ($updateContract['result'] == 'error')
                return response()->json($updateContract, 500);
            else
                return response()->json($updateContract);

        } catch (\Exception $e) {
            $err = [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                // 'trace' => $e->getTraceAsString(),
            ];

            Log::error("Error en updateZohoPTP: " . $e->getMessage() . "\n" . json_encode($err, JSON_PRETTY_PRINT));
            return response()->json([
                $err
            ]);
        }
    }

    public function updateZohoPlaceToPay($request, $result, $requestIdRequestSubscription)
    {
        $requestsSubscription = PlaceToPayTransaction::where(['requestId' => $requestIdRequestSubscription])->get()->first();

        $dataUpdate = [
            'Anticipo' => $requestsSubscription->first_installment,
            'Cantidad' => $requestsSubscription->quotes,
            'Monto_de_cuotas_restantes' => $requestsSubscription->isAdvancedSubscription() ? $requestsSubscription->first_installment : $requestsSubscription->installmentsToPay(),
            'Cuotas_restantes_sin_anticipo' => $requestsSubscription->isAdvancedSubscription() ? $requestsSubscription->quotes - 1 : null,
            'Fecha_de_Vto' => date('Y-m-d'),
            'Status' => 'Contrato Efectivo',
            'Medio_de_Pago' => 'Placetopay',
            'Modalidad_de_pago_del_Anticipo' => 'Placetopay',
            'Saldo' => $result['request']['payment']['amount']['total'],
            'Es_Suscri' => $requestsSubscription->isSubscription(),
            'Suscripcion_con_Parcialidad' => $requestsSubscription->isAdvancedSubscription(),
            'Discount' => abs($request['adjustment']),


            'DNI' => '',
            'L_nea_nica_3' => $result['request']['payer']['document'],
            'Tel_fono_Facturacion' => $result['request']['payer']['mobile'],
            'L_nea_nica_6' => $result['request']['payer']['name'] . " " . $result['request']['payer']['surname'],
            'Email' => $result['request']['payer']['email'],
            'Billing_Street' => $result['request']['payer']['address']['street'],
        ];

        $updateContract = $this->updateRecord('Sales_Orders', $dataUpdate, $request->contractId, true);

        return $updateContract;
        //     if ($updateContract['result'] == 'error')
        //         return response()->json($updateContract, 500);
        //     else
        //         return response()->json($updateContract);
    }

    private function getIdentification($identification, $country)
    {

        if ($country == "Chile" && strpos(strval($identification), '-') == false) {
            return substr(strval($identification), 0, -1) . '-' . substr(strval($identification), -1);
        }

        return strval($identification);
    }

    public function obtainData(Request $request)
    {

        $data = $request->all();

        $key = $data['key'];
        $id = $data['id'];

        $answer = [];
        $answer['detail'] = 'wrong key';
        $answer['status'] = 'error';

        if ($key == '9j9fj0Do204==3fja134') {
            $sale = $this->fetchRecordWithValue('Sales_Orders', 'id', $id, true);

            if ($sale == 'error') {
                $sale = $this->fetchRecordWithValue('Sales_Orders', 'SO_Number', $id, true);

                if ($sale == 'error') {
                    $answer['detail'] = 'Sale not found';
                    $answer['status'] = 'error';
                    return $answer;
                }
            }

            $answer['products'] = Contract::getProducts($sale->getLineItems());
            $answer['sale'] = $sale->getData();

            $contactId = $sale->getFieldValue('Contact_Name')->getEntityId();
            $contact = $this->fetchRecordWithValue('Contacts', 'id', $contactId, true);

            $answer['contact'] = $contact->getData();
            $answer['status'] = 'ok';
        }

        if ($answer['status'] == 'error')
            return response()->json($answer, 500);
        else
            return response()->json($answer);
    }

    private function getContractZoho($number)
    {
        $sale = $this->fetchRecordWithValue('Sales_Orders', 'id', $number);

        if ($sale == 'error') {
            $sale = $this->fetchRecordWithValue('Sales_Orders', 'SO_Number', $number);

            if ($sale == 'error') {
                $answer['detail'] = 'Sale not found';
                $answer['status'] = 'error';
                return $answer;
            } else {
                return $sale;
            }
        } else {
            return $sale;
        }
    }

    public function createLead(Request $request)
    {
        $data = $request->all();

        $data['source_lead'] = isset($data['source_lead']) ? SourceLead::where('id', $data['source_lead'])->first()->name : null;
        $data['profession'] = Profession::where('id', $data['profession'])->first()->name;
        $data['speciality'] = Speciality::where('id', $data['speciality'])->first()->name;
        $data['method_contact'] = MethodContact::where('id', $data['method_contact'])->first()->name;
        $data['user_email'] = $request->user()->email;

        $leadData = $this->processLeadData($data);

        $leadIsDuplicate = $this->updateFetchDuplicateLeads($leadData['Email']);

        if ($leadIsDuplicate)
            $leadData['Lead_Duplicado'] = true;

        $newLead = $this->createNewRecord('Leads', $leadData);

        if ($newLead['result'] == 'error')
            return response()->json($newLead, 500);
        else
            return response()->json($newLead);
    }

    public function createContact(Request $request)
    {
        $data = $request->all();

        //lo primero que haremos es intentar crear el contacto
        $contactData = array(
            'First_Name' => $data['name'],
            'Last_Name' => $data['username'],
            'Email' => $data['email'],
            'DNI' => $data['dni'],
            'Home_Phone' => $data['telephone'],
            'Pais' => $data['country'],
        );

        $newContact = $this->createNewRecord('Contacts', $contactData);

        if ($newContact['result'] == 'error')
            return response()->json($newContact, 500);
        else
            return response()->json($newContact);
    }

    private function createAddress($data)
    {
        $answer = [];
        $answer['id'] = '';
        $answer['result'] = '';
        //Guardo contacto en variable
        $contactData = $data['contact'];

        //armamos data de la dire y la creamos
        $addressData = array(
            'Calle' => $contactData['street'],
            'C_digo_Postal' => $contactData['postal_code'],
            'Name' => 'direccion',
            'Contacto' => $data['contact_id'],
            'Provincia' => $contactData['province_state'],
            'Pais' => $contactData['country'],
            'Localidad1' => $contactData['locality'],
            'Tipo_Dom' => "Particular"
        );

        //primero vamos a ver si existe una dirección con el mismo ID de contacto
        //para no repetir
        $existAddress = $this->fetchRecordWithValue('Domicilios', 'Contacto', $data['contact_id']);

        //esto significa que no existe
        if ($existAddress == 'error') {
            $newAddress = $this->createNewRecord('Domicilios', $addressData);
        } else //en cambio, si existe, actualizo
        {
            $newAddress = $this->updateRecord('Domicilios', $addressData, $existAddress->getEntityId());
        }

        return ($newAddress);
    }

    public function createAddressRequest(Request $request)
    {
        $data = $request->all();

        $address = $this->createAddress($data);

        if ($address['result'] == 'error')
            return response()->json($address, 500);
        else
            return response()->json($address);
    }

    public function createSale(Request $request)
    {
        $progress = PurchaseProgress::find($request->idPurchaseProgress);
        $products = $progress->contract->products->toArray();

        //armo el product details en base a las cosas que compró el usuario...
        $productDetails = $this->buildProductDetails($products);

        if ($productDetails != 'error') {
            $saleData = array(
                'Subject' => 'etc',
                //*
                'Status' => 'Contrato Pendiente',
                //*
                'Contact_Name' => $progress->contact->entity_id_crm,
                //'Cantidad' => $data['installments'],
                //'Fecha_de_Vto' => date('Y-m-d'),//*
                //'L_nea_nica_6' => $data['name'],
                //'L_nea_nica_3' => $data['identification'],
                //'Billing_Street' => $data['address'],
                //'Tipo_De_Pago' => $data['payment_type'],
                '[products]' => $productDetails,
                //* producto->id
                'Pais' => $progress->country,
                //'Anticipo' => strval($data['payment_in_advance']),
                //'Cuotas_restantes_sin_anticipo' => $data['left_installments'],
                //'Medio_de_Pago' => $data['left_payment_type'],
                //'Cuotas_totales' => 1,//*
                'Currency' => $progress->contract->currency,
                //'Modalidad_de_pago_del_Anticipo' => $data['left_payment_type'],
                //'Tipo_IVA' => 'Consumidor Final - ICF',
                'Discount' => isset($request->discount) ? $request->discount : 0
            );

            $newSale = $this->createRecordSale($saleData);

            if ($newSale['result'] == 'error') {
                return response()->json($newSale, 500);
            } else {
                $progress->contract->update(['entity_id_crm' => $newSale['id']]);
                return response()->json($newSale);
            }

        } else {
            $answer['id'] = '';
            $answer['result'] = 'error';

            return response()->json(['detail' => 'SKU incorrect'], 500);
        }

    }

    public function createRecordSale($data)
    {
        $answer = array();
        $answer['id'] = '';
        $answer['result'] = 'error';
        $answer['detail'] = '';

        try {
            $record = ZCRMRestClient::getInstance()->getRecordInstance("Sales_Orders", null); // To get record instance
            //campos sales orders
            foreach ($data as $k => $v) {
                if ($k != '[products]')
                    $record->setFieldValue($k, $v);
            }

            //productos
            foreach ($data['[products]'] as $p) {
                $product = ZCRMInventoryLineItem::getInstance(null); // To get ZCRMInventoryLineItem instance

                $product->setListPrice($p['List Price']);
                $product->setProduct(ZCRMRecord::getInstance("Products", $p['Product Id']));
                $product->setQuantity($p['Quantity']);

                if ($p['Discount'] > 0)
                    $product->setDiscountPercentage($p['Discount']);

                $record->addLineItem($product);
            }

            $responseIns = $record->create();

            if ($responseIns->getHttpStatusCode() == 201) {
                $answer['result'] = 'ok';
                $aux = $responseIns->getDetails();
                $answer['id'] = $aux['id'];
            }
        } catch (ZCRMException $e) {

            if (!empty($e->getExceptionDetails()))
                $answer['detail'] = $e->getExceptionDetails();
            else
                $answer['detail'] = $e->getMessage();

            Log::error($e);

        }

        return ($answer);
    }

    public function createRecordQuote($data)
    {
        $answer = array();
        $answer['id'] = '';
        $answer['result'] = 'error';
        $answer['detail'] = '';

        try {
            $record = ZCRMRestClient::getInstance()->getRecordInstance("Quotes", null); // To get record instance
            //campos sales orders
            foreach ($data as $k => $v) {
                if ($k != '[products]')
                    $record->setFieldValue($k, $v);
            }

            //productos
            foreach ($data['[products]'] as $p) {
                $product = ZCRMInventoryLineItem::getInstance(null); // To get ZCRMInventoryLineItem instance

                $product->setListPrice($p['List Price']);
                $product->setProduct(ZCRMRecord::getInstance("Products", $p['Product Id']));
                $product->setQuantity($p['Quantity']);

                if ($p['Discount'] > 0)
                    $product->setDiscountPercentage($p['Discount']);
                /*
                                $taxInstance1 = ZCRMTax::getInstance("5344455000002958477");
                                $taxInstance1->setPercentage(10);
                                $taxInstance1->setValue(100);
                                $product->addLineTax($taxInstance1); */

                $record->addLineItem($product);
            }

            $responseIns = $record->create();

            if ($responseIns->getHttpStatusCode() == 201) {
                $answer['result'] = 'ok';
                $aux = $responseIns->getDetails();
                $answer['id'] = $aux['id'];
            }
        } catch (ZCRMException $e) {

            if (!empty($e->getExceptionDetails()))
                $answer['detail'] = $e->getExceptionDetails();
            else
                $answer['detail'] = $e->getMessage();

            Log::error($e);

        }

        return ($answer);
    }

    //arma el detalle de productos para el contrato
    private function buildProductDetails($products)
    {
        $answer = array();
        //arma y reemplaza sku por ID de producto en zoho
        foreach ($products as $p) {
            $p['product_code'] = trim($p['product_code']); //Remove whitespace from product_code
            $rec = $this->fetchRecordWithValue('Products', 'Product_Code', $p['product_code']);

            if ($rec != 'error') {
                $answer[] = array(
                    'Product Id' => $rec->getEntityId(),
                    //*
                    'Quantity' => (int) $p['quantity'],
                    'List Price' => (float) $p['price'],
                    //'List Price #USD' => (float)$p['price_usd'],
                    //'List Price #Local Currency' => (float)$p['price'],
                    'Discount' => (float) $p['discount']
                );
            } else {
                $answer = "error";
                break;
            }
        }


        return ($answer);
    }

    private function getIdentificationOfContact($country, $contact)
    {
        switch ($country) {
            case 'Chile':
                return $contact['rut'];
            case 'México':
                return $contact['rfc'];
            default:
                return $contact['dni'];

        }
    }

    public function convertLead(Request $request)
    {

        $genderOptions = [
            (object) ['id' => 1, 'name' => 'Masculino'],
            (object) ['id' => 2, 'name' => 'Femenino'],
            (object) ['id' => 3, 'name' => 'Prefiero no aclararlo']
        ];
        $progress = PurchaseProgress::where('id', $request->idPurchaseProgress)->first();
        $userOfProgress = User::find($progress->user_id);
        $leadInProgress = $progress->lead->toArray();

        $data = $request->all();
        $leadId = $data['lead_id'];

        $gender = collect($genderOptions)->firstWhere('id', $data['contact']['sex'])->name;

        $identification = $this->getIdentificationOfContact($progress->country, $data['contact']);

        $additionalData = [
            'DNI' => $identification,
            'Sexo' => $gender,
            'Date_of_Birth' => $data['contact']['date_of_birth'],
            'Nro_Matr_cula' => $data['contact']['registration_number'],
            'rea_donde_trabaja' => $data['contact']['area_of_work'],
            'Inter_s_de_Formaci_n' => $data['contact']['training_interest'],
            'Plataforma' => 'Venta Presencial',
        ];

        $fetchContact = $this->fetchRecordWithValue("Contacts", 'DNI', $additionalData['DNI']);

        if ($fetchContact != 'error') {
            $entityId = $fetchContact->getEntityId();
            $leadConvertToContact = "El lead no fue convertido, se encontro un contacto con el mismo DNI y se utilizo el contacto ya existente";
            $additionalData['First_Name'] = $leadInProgress["name"];
            $additionalData['Last_Name'] = $leadInProgress["username"];
            $additionalData['Telefono_infobip'] = $leadInProgress["telephone"];
            $additionalData['Home_Phone'] = $leadInProgress["telephone"];
            $additionalData['Phone'] = $leadInProgress["telephone"];
            $additionalData['Email'] = $leadInProgress["email"];
            $additionalData['Fuente_del_Lead'] = [$leadInProgress["source_lead"]];
            $additionalData['FUENTE'] = $leadInProgress["source_lead"]; //hay que definir donde buscamos el dato
            $additionalData['Plataforma'] = 'Venta Presencial';
            $additionalData['Lead_Status'] = 'Contacto urgente';
            $additionalData['Pais'] = $progress->country;
            $additionalData['pp'] = $leadInProgress["profession"];
            $additionalData['Especialidad'] = [$leadInProgress["speciality"]];
            $additionalData['Canal_de_Contactaci_n'] = [$leadInProgress["method_contact"]];
            $additionalData['EIRL'] = $userOfProgress['email'];
            $leadModule = ZCRMRestClient::getInstance()->getModuleInstance('Leads');
            $leadModule->deleteRecords([$leadId]);
        } else {
            $leadConvertToContact = $this->convertRecord($leadId, 'Leads');

            if (!empty($leadConvertToContact['id'])) {
                $entityId = $leadConvertToContact['id'];
            }
        }

        $updatedContact = $this->updateRecord("Contacts", $additionalData, $entityId, false);

        $addressParams = array_merge($data, ['contact_id' => $entityId]);
        $address = $this->createAddress($addressParams);

        if ($address['result'] == 'error' || $updatedContact['result'] == 'error') {
            return response()->json(['lead' => $leadConvertToContact, 'contact' => $updatedContact, 'address' => $address], 500);
        } else {
            return response()->json(['lead' => $leadConvertToContact, 'contact' => $updatedContact, 'address' => $address]);
        }
    }

    private function convertRecord($id, $type)
    {
        $answer['result'] = 'error';
        $answer['id'] = '';
        $answer['detail'] = '';

        try {
            $record = ZCRMRestClient::getInstance()->getRecordInstance($type, $id); // To get record instance
            $contact = ZCRMRecord::getInstance("Contacts", null); // to get the record of deal in form of ZCRMRecord insatnce
            $details = array("overwrite" => true);
            $responseIn = $record->convert($contact, $details); // to convert record

            $answer['result'] = 'ok';
            $answer['id'] = $responseIn["Contacts"];

        } catch (ZCRMException $e) {
            $handle = $this->handleError($e, $type, []);

            if ($handle != 'error') {
                $answer['result'] = 'duplicate';
                $answer['id'] = $handle;
            } else {
                $answer['result'] = 'error';

                if (!empty($e->getExceptionDetails()))
                    $answer['detail'] = $e->getExceptionDetails();
                else
                    $answer['detail'] = $e->getMessage();

                Log::error($e);
            }
        }

        return ($answer);
    }

    private function processLeadData($data)
    {
        //hay contactos?
        if ($this->fetchRecordWithValue('Contacts', 'Email', $data["email"]) == "error") {
            $leadData['Es_Contacto'] = false;
        } else {
            $leadData['Es_Contacto'] = true;
        }

        $leadData['First_Name'] = $data["name"];
        $leadData['Last_Name'] = $data["username"];
        $leadData['Phone'] = $data["telephone"];
        $leadData['Email'] = $data["email"];
        $leadData['Fuente_del_Lead'] = [$data["source_lead"]];
        $leadData['FUENTE'] = $data["source_lead"];
        $leadData['Plataforma'] = 'Venta Presencial';
        $leadData['Lead_Status'] = 'Contacto urgente';
        $leadData['Pais'] = $data["country"];
        $leadData['pp'] = $data["profession"];
        $leadData['Especialidad'] = [$data["speciality"]];
        $leadData['Canal_de_Contactaci_n'] = [$data["method_contact"]];
        $leadData['EIRL'] = $data["user_email"];
        $leadData['*owner'] = $this->emi_owner;

        return $leadData;
    }

    private function updateFetchDuplicateLeads($mail)
    {
        //hay leads con ese mail?
        $searchBy = "((Email:equals:" . $mail . ")and(Lead_Status:equals:Contacto urgente))";
        $sameUserLeads = $this->fetchRecords('Leads', $searchBy); //<-- busca records para saber si el usuario ya intentó comprar anteriormente

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

    public function getProducts(Request $request, $iso)
    {
        $data = $request->all();
        try {
            $response = Http::asForm()->post("https://www.oceanomedicina.net/proxy/proxy2.php?url=https://www.oceanomedicina.com/api_landing.php", ['pais' => $iso]);

            // Verificar si la respuesta HTTP fue exitosa
            if ($response->successful()) {
                $data = json_decode($response->body());

                return response()->json($data);
            } else {
                // Manejar posibles errores o excepciones
                return response()->json([
                    'error' => 'Error al obtener los productos'
                ], $response->status());
            }
        } catch (\Exception $e) {
            // Manejar excepciones no controladas
            return response()->json([
                'error' => 'Error al obtener los productos: ' . $e->getMessage()
            ], 500);
        }
    }

    //traer usuario
    public function getUser($id)
    {
        $answer = 'error';

        $record = null;

        try {

            $apiResponse = ZCRMOrganization::getInstance()->getUser($id);
            $user = array($apiResponse->getData());

            $answer = $user[0];

        } catch (\Exception $e) {
            Log::error($e);
        }

        return ($answer);

    }

    public function getProductsWithoutIso(Request $request)
    {
        $data = $request->all();
        try {
            $response = Http::asForm()->post("https://www.oceanomedicina.net/proxy/proxy2.php?url=https://www.oceanomedicina.com/api_landing.php", ['pais' => 'ar']);

            // Verificar si la respuesta HTTP fue exitosa
            if ($response->successful()) {
                $data = json_decode($response->body());

                return response()->json($data);
            } else {
                // Manejar posibles errores o excepciones
                return response()->json([
                    'error' => 'Error al obtener los productos'
                ], $response->status());
            }
        } catch (\Exception $e) {
            // Manejar excepciones no controladas
            return response()->json([
                'error' => 'Error al obtener los productos: ' . $e->getMessage()
            ], 500);
        }
    }

}
