<?php

namespace App\Services\Webhooks;

use App\Clients\ZohoClient;
use App\Interfaces\ISaveWebhookCrmService;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\exception\ZCRMException;
use zcrmsdk\crm\setup\users\ZCRMUser;
use zcrmsdk\oauth\exception\ZohoOAuthException;

class SaveWebhookZohoCrmService implements ISaveWebhookCrmService
{
    private ZohoClient $client;

    public function __construct(ZohoClient $client)
    {
        $this->client = $client;
    }

    /**
     * @throws ZohoOAuthException
     * @throws ZCRMException
     */
    public function saveWebhook2Crm(array $data): ?string
    {
        return "todavia no funciona";
        $table = '';//@todo nombre de la tabla
        $client = $this->client->getClient();
        /** @var ZCRMUser $a */
        $user = $client->getCurrentUser()->getData()[0];
        \Log::info("Connected to Zoho", [$user->getCountry(), $user->getId(), $user->getEmail()]);
        $client->getModuleInstance($table);
        $record = ZCRMRecord::getInstance($table, null);
        //@todo completar con los nombres de los campos de zoho
        $record->setFieldValue('', $data['amount']);
        $record->setFieldValue('', $data['payment_id']);
        $record->setFieldValue('', $data['pay_date']);
        $record->setFieldValue('', $data['pay_state']);
        $record->setFieldValue('', $data['gateway']);

        return $record->create()->getResponse();
    }
}
