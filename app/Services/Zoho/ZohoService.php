<?php

namespace App\Services\Zoho;

use App\Clients\ZohoClient;
use App\Interfaces\IClient;
use Illuminate\Support\Facades\Log;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\exception\ZCRMException;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use zcrmsdk\oauth\exception\ZohoOAuthException;

class ZohoService
{
    protected IClient $client;

    public function __construct(IClient $client)
    {
        $this->client = $client;
    }

    public function buildTablePaymentDetail($contractId, $detailApprovedPayment)
    {
        $table = $this->getSaleOrderPaymentDetail($contractId);
        $table[] = $detailApprovedPayment;

        return $table;
    }

    public function fetchRecordWithValue($module, $field, $value)
    {
        $answer = 'error';
        $record = null;
        try {
            $this->client->getClient();
            $moduleIns = ZCRMRestClient::getInstance()->getModuleInstance($module); //To get module instance
            $response = $moduleIns->searchRecordsByCriteria('(' . $field . ':equals:' . $value . ')');
            $records = $response->getData(); //To get response data
            $answer = $records[0];
        } catch (\zcrmsdk\crm\exception\ZCRMException $e) {
            Log::debug($e);
        }
        return ($answer);
    }

    public function getSaleOrderPaymentDetail($id)
    {
        try {
            $this->client->getClient();
            $moduleIns = ZCRMRestClient::getInstance()->getModuleInstance("Sales_Orders"); //To get module instance
            $record = $moduleIns->getRecord($id);
            $data = $record->getData(); //To get response data

            $Paso_5_Detalle_pagos = $record->getData()->getFieldValue("Paso_5_Detalle_pagos");
            $Banco_emisor = $record->getData()->getFieldValue("Banco_emisor");

            return $Paso_5_Detalle_pagos;
        } catch (ZCRMException $e) {

            if (!empty($e->getExceptionDetails()))
                $answer['detail'] = $e->getExceptionDetails();
            else
                $answer['detail'] = $e->getMessage();

            Log::error($e);
        }
        return ($answer);
    }

    public function updateTablePaymentsDetails($contractId,$session,$subscription){
        $detailApprovedPayment = [
            'Fecha_Cobro' => date('Y-m-d', strtotime($subscription->date_to_pay)),
            'Num_de_orden_o_referencia_ext' => $session->reference,
            'Cobro_ID' => $subscription->reference,
            'Monto' => $subscription->total,
            'Numero_de_cobro' => $subscription->nro_quote,
            'Origen_Pago' => 'SPP',
        ];

        $dataUpdate = [
            'Paso_5_Detalle_pagos' => $this->buildTablePaymentDetail($contractId,$detailApprovedPayment)
        ];

        return $this->updateRecord('Sales_Orders', $dataUpdate, $contractId, true);
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

}
