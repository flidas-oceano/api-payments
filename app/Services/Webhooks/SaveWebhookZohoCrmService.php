<?php

namespace App\Services\Webhooks;

use App\Clients\ZohoClient;
use App\Interfaces\ICrmClient;
use App\Interfaces\ISaveWebhookCrmService;
use zcrmsdk\crm\crud\ZCRMInventoryLineItem;
use zcrmsdk\crm\crud\ZCRMModule;
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
    public function saveWebhook2Crm(array $data): ?string
    {
        return 'todavia no funciona';
        $table = 'Sales_Orders';//@todo nombre de la tabla
        $client = $this->client->getClient();
        /** @var ZCRMUser $user */
        $user = $client->getCurrentUser()->getData()[0];
        //dd($client->getAllModules() );
        \Log::info("Connected to Zoho", [$user->getId(), $user->getEmail()]);

        $moduleIns = ZCRMRestClient::getInstance()->getModuleInstance($table); //To get module instance

        $field = 'otro_so';
        $so = '5344455000003220095_0';
        $response = $moduleIns->searchRecordsByCriteria('(' . $field . ':equals:' . $so . ')');
        $records = $response->getData(); //To get response data
        /** @var ZCRMRecord $answer */
        $answer = $records[0];
        $product = ZCRMInventoryLineItem::getInstance($answer);
        dd($product);
        $record = ZCRMRecord::getInstance($table, null)->getData();
        //@todo completar con los nombres de los campos de zoho
        $record->setFieldValue('', $data['amount']);
        $record->setFieldValue('', $data['payment_id']);
        $record->setFieldValue('', $data['pay_date']);
        $record->setFieldValue('', $data['pay_state']);
        $record->setFieldValue('', $data['gateway']);

        return $record->create()->getResponse();
    }
}
