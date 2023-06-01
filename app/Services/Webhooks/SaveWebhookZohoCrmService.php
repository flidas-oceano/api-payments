<?php

namespace App\Services\Webhooks;

use App\Interfaces\ICrmClient;
use App\Interfaces\ISaveWebhookCrmService;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\exception\ZCRMException;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use zcrmsdk\crm\setup\users\ZCRMUser;
use zcrmsdk\oauth\exception\ZohoOAuthException;

class SaveWebhookZohoCrmService implements ISaveWebhookCrmService
{
    private ICrmClient $client;

    public function __construct(ICrmClient $client)
    {
        $this->client = $client;
    }

    /**
     * @throws ZohoOAuthException
     * @throws ZCRMException
     */
    public function saveWebhook2Crm(array $data): ?array
    {
        $table = 'Sales_Orders';
        $client = $this->client->getClient();
        /** @var ZCRMUser $user */
        $user = $client->getCurrentUser()->getData()[0];
        \Log::info("Connected to Zoho", [$user->getId(), $user->getEmail()]);
        $moduleIns = ZCRMRestClient::getInstance()->getModuleInstance($table);
        $field = 'otro_so';
        $so = $data['number_so_om'];
        $response = $moduleIns->searchRecordsByCriteria('(' . $field . ':equals:' . $so . ')');
        $records = $response->getData();
        /** @var ZCRMRecord $answer */
        $answer = $records[0];
        $entityId = $answer->getEntityId();
        $salesResponse = $moduleIns->getRecord($entityId);
        $salesRecord = $salesResponse->getData();

        $arrayStep5Subform = [];
        $step5Subform = $salesRecord->getFieldValue("Paso_5_Detalle_pagos");//dd($step5Subform);
        if (isset($step5Subform[0])) {
            foreach ($step5Subform as $item) {
                $arrayStep5Subform[] = [
                    'Cobro_ID' => $item['Cobro_ID'],
                    'Fecha_Cobro' => $item['Fecha_Cobro'],
                    'Numero_de_cobro' => $item['Numero_de_cobro']
                ];
            }
        }
        $arrayStep5Subform[] = [
            'Cobro_ID' => $data['payment_id'],
            'Fecha_Cobro' => $data['pay_date'],
            'Numero_de_cobro' => sizeof($arrayStep5Subform) + 1
        ];

        $answer->setFieldValue("Paso_5_Detalle_pagos", $arrayStep5Subform);
        $response = $answer->update();

        return $response->getResponseJSON()['data'];
    }
}
