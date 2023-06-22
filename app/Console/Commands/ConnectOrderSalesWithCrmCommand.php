<?php

namespace App\Console\Commands;

use App\Services\MercadoPago\ReadPayment;
use App\Services\SalesOrders\ReadOrderSalesService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use zcrmsdk\crm\crud\ZCRMRecord;

class ConnectOrderSalesWithCrmCommand extends Command
{
    protected string $name = 'sales-order:crm';

    protected string $signature = 'sales-order:crm';

    protected string $description = 'Connect with zoho crm and mercado pago, to update recent payments' ;

    private ReadOrderSalesService $service;
    private ReadPayment $readPayment;

    public function __construct(ReadOrderSalesService $service, ReadPayment $readMercadoPago)
    {
        $this->setName($this->name);
        parent::__construct();
        $this->service = $service;
        $this->readPayment = $readMercadoPago;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $output->writeln("Executing " . __CLASS__);
            $result = $this->service->listOrderSalesCrm(1, 201);
            foreach ($result as $item) {
                $result = $this->readPayment->findById($item->getFieldValue('otro_so'), 'mx_msk');
                if ($result->getResults())
                    dd(  $result->getResults() );
            }


            dd(sizeof($result));//@todo to be continued...
            //@todo ...
        } catch (\Exception $e) {
            $msg = "ERROR: ".$e->getMessage();
            $output->writeln($msg);
            \Log::error($msg);
        }

        return 0;
    }

}
