<?php

namespace App\Console\Commands;

use App\Services\SalesOrders\ReadOrderSalesService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConnectOrderSalesWithCrmCommand extends Command
{
    protected string $name = 'sales-order:crm';

    protected string $signature = 'sales-order:crm';

    protected string $description = 'Connect with zoho crm and mercado pago, to update recent payments' ;

    private ReadOrderSalesService $service;

    public function __construct(ReadOrderSalesService $service)
    {
        $this->setName($this->name);
        parent::__construct();
        $this->service = $service;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $output->writeln("Executing " . __CLASS__);
            $this->service->listOrderSalesCrm();
            //@todo ...
        } catch (\Exception $e) {
            $msg = "ERROR: ".$e->getMessage();
            $output->writeln($msg);
            \Log::error($msg);
        }

        return 0;
    }

}
