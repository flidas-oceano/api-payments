<?php

namespace App\Services\SalesOrders;

use App\Clients\ZohoMskClient;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use zcrmsdk\oauth\exception\ZohoOAuthException;

class ReadOrderSalesService
{
    protected ZohoMskClient $client;

    public function __construct(ZohoMskClient $client)
    {
        $this->client = $client;
    }

    /**
     * @throws ZohoOAuthException
     */
    public function listOrderSalesCrm()
    {
        $table = 'Sales_Orders';
        $this->client->getClient();
        $param_map = array("page" => "1", "per_page" => "200");
        $header_map = array("if-modified-since" => "2019-10-10T15:26:49+05:30");
        $moduleOrderSales =  ZCRMRestClient::getInstance()->getModuleInstance($table);
        $result = $moduleOrderSales->getRecords($param_map, $header_map);
        /** @var ZCRMRecord $item */
        foreach ($result->getData() as $item) {
            $numberSoOM = ($item->getFieldValue('otro_so'));
            dd($numberSoOM); //@todo ya trae el numero so
        }
        dd($result->getResponseJSON());
    }
}
