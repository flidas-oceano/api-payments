<?php

namespace App\Services\Zoho;

use App\Clients\ZohoClient;
use App\Interfaces\IClient;
use App\Models\Contract;
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

    // Función para eliminar elementos duplicados en base a 'Numero_de_cobro'
    private function removeDuplicatesByNumeroCobro($table)
    {
        $uniqueTable = [];
        $seenCobros = [];

        foreach ($table as $paymentDetail) {
            if (!isset($paymentDetail['Numero_de_cobro'])) {
                continue;
            }

            $numeroCobro = $paymentDetail['Numero_de_cobro'];

            if (!in_array($numeroCobro, $seenCobros)) {
                $uniqueTable[] = $paymentDetail;
                $seenCobros[] = $numeroCobro;
            }
        }

        return $uniqueTable;
    }

    public function buildTablePaymentDetail($contractId, $detailApprovedPayment)
    {


        $table = $this->getSaleOrderPaymentDetail($contractId);
        // Variable para rastrear si se encontró el número de cuota en $table

        $uniqueTable = $this->removeDuplicatesByNumeroCobro($table);

        $foundPayment = false;

        foreach ($uniqueTable as $key => $paymentDetail) {
            if ($paymentDetail['Numero_de_cobro'] == $detailApprovedPayment['Numero_de_cobro']) {
                // Reemplazar la entrada existente con $detailApprovedPayment
                $table[$key] = $detailApprovedPayment;
                $foundPayment = true;
                break; // Salir del bucle una vez que se reemplace el valor
            }
        }

        // Si no se encontró el número de cuota, agregar $detailApprovedPayment al final de $table
        if (!$foundPayment) {
            $uniqueTable[] = $detailApprovedPayment;
        }

        return $uniqueTable;
    }
    public function buildTablePaymentDetailNew($request)
    {

        $detailApprovedPayment = Contract::buildDetailApprovedPayment($request);

        $table = $this->getSaleOrderPaymentDetail($request->contractId);
        // Variable para rastrear si se encontró el número de cuota en $table

        $uniqueTable = $this->removeDuplicatesByNumeroCobro($table);

        $foundPayment = false;

        foreach ($uniqueTable as $key => $paymentDetail) {
            if ($paymentDetail['Numero_de_cobro'] == $detailApprovedPayment['Numero_de_cobro']) {
                // Reemplazar la entrada existente con $detailApprovedPayment
                $table[$key] = $detailApprovedPayment;
                $foundPayment = true;
                break; // Salir del bucle una vez que se reemplace el valor
            }
        }

        // Si no se encontró el número de cuota, agregar $detailApprovedPayment al final de $table
        if (!$foundPayment) {
            $uniqueTable[] = $detailApprovedPayment;
        }

        return $uniqueTable;
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

    public function updateTablePaymentsDetails($contractId, $session, $subscription)
    {

        if($session->isOneTimePayment()){
            $Fecha_Cobro = date('Y-m-d', strtotime($session->date));
            $Cobro_ID = $session->reference;
            $Monto = $session->total;
            $Numero_de_cobro = 1;
            $Fecha_de_primer_cobro = date('Y-m-d', strtotime($session->date));
            $stripe_subscription_id = $session->reference;
        }else{
            $Fecha_Cobro = date('Y-m-d', strtotime($subscription->date_to_pay));
            $Cobro_ID = $subscription->reference;
            $Monto = $subscription->total;
            $Numero_de_cobro = $subscription->nro_quote;
            $Fecha_de_primer_cobro = date('Y-m-d', strtotime($subscription->date_to_pay));
            $stripe_subscription_id = $session->reference;
        }

        $detailApprovedPayment = [
            'Fecha_Cobro' => $Fecha_Cobro,
            'Num_de_orden_o_referencia_ext' => $session->reference,
            'Cobro_ID' => $Cobro_ID,
            'Monto' => $Monto,
            'Numero_de_cobro' => $Numero_de_cobro,
            'Origen_Pago' => 'SPP',
        ];

        $dataUpdate = [
            'Paso_5_Detalle_pagos' => $this->buildTablePaymentDetail($contractId, $detailApprovedPayment)
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

    public function getContractZoho($number)
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

}
