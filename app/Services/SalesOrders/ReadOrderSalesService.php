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
     * @return ZCRMRecord[]
     */
    public function listOrderSalesCrm($page = 1, $perPage = 200): array
    {
        $table = 'Sales_Orders';
        $this->client->getClient();
        $param_map = array("page" => $page, "per_page" => $perPage);
        $header_map = array("if-modified-since" => "2023-01-01T00:00:00+05:30");
        $moduleOrderSales =  ZCRMRestClient::getInstance()->getModuleInstance($table);
        $result = $moduleOrderSales->getRecords($param_map, $header_map);

        return $result->getData();
    }


}
