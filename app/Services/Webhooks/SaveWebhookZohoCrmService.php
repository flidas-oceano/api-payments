<?php

namespace App\Services\Webhooks;

use App\Enums\GatewayEnum;
use App\Interfaces\IClient;
use App\Interfaces\ISaveWebhookCrmService;
use Symfony\Component\Console\Output\OutputInterface;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\exception\ZCRMException;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use zcrmsdk\crm\setup\users\ZCRMUser;
use zcrmsdk\oauth\exception\ZohoOAuthException;

class SaveWebhookZohoCrmService implements ISaveWebhookCrmService
{
    private IClient $client;

    public function __construct(IClient $client)
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
        /** @var ZCRMRecord $zRecord */
        $zRecord = $records[0];
        $entityId = $zRecord->getEntityId();
        $salesResponse = $moduleIns->getRecord($entityId);
        $salesRecord = $salesResponse->getData();

        $arrayStep5Subform = [];
        $step5Subform = $salesRecord->getFieldValue("Paso_5_Detalle_pagos");
        $numberCharge = 1;
        if (isset($step5Subform[0])) {
            foreach ($step5Subform as $item) {
                if ($data['payment_id'] != $item['Cobro_ID']) {
                    $arrayStep5Subform[] = [
                        'Cobro_ID' => $item['Cobro_ID'],
                        'Fecha_Cobro' => $item['Fecha_Cobro'],
                        'Numero_de_cobro' => $numberCharge,
                        'Origen_Pago' => $data['origin'],
                        'Num_de_orden_o_referencia_ext' => $item['Num_de_orden_o_referencia_ext'],
                        'Monto' => $item['Monto'],
                    ];
                    $numberCharge++;
                }
            }
        } else {
            \Log::info("step5Subform - is EMPTY", [$user->getId(), $user->getEmail()]);
        }
        $arrayStep5Subform[] = [
            'Cobro_ID' => $data['payment_id'],
            'Fecha_Cobro' => $data['pay_date'],
            'Numero_de_cobro' => $numberCharge,//sizeof($arrayStep5Subform) + 1,
            'Origen_Pago' => $data['origin'],
            'Num_de_orden_o_referencia_ext' => $data['id'],
            'Monto' => $data['amount_charged'],
        ];

        $zRecord->setFieldValue("Paso_5_Detalle_pagos", $arrayStep5Subform);
        $response = $zRecord->update();

        return $response->getResponseJSON()['data'];
    }
}
