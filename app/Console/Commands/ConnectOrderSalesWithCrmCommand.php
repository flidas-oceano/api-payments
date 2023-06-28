<?php

namespace App\Console\Commands;

use App\Clients\ZohoMskClient;
use App\Services\MercadoPago\ReadPayment;
use App\Services\SalesOrders\ReadOrderSalesService;
use App\Services\Webhooks\SaveWebhookZohoCrmService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConnectOrderSalesWithCrmCommand extends Command
{
    protected string $name = 'sales-order:crm';

    protected string $signature = 'sales-order:crm';

    protected string $description = 'Connect with zoho crm and mercado pago, to update recent payments' ;

    private ReadOrderSalesService $service;
    private ReadPayment $readPayment;

    private SaveWebhookZohoCrmService $crm;

    public function __construct(ReadOrderSalesService $service, ReadPayment $readMercadoPago)
    {
        $this->setName($this->name);
        parent::__construct();
        $this->service = $service;
        $this->readPayment = $readMercadoPago;
        $this->crm = new SaveWebhookZohoCrmService(new ZohoMskClient());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $output->writeln(" - Executing " . __CLASS__);
            $result = $this->service->listOrderSalesCrm(1, 10);
            $output->writeln(" - Listed result from CRM " . sizeof($result)." entries!");
            $payments = $this->listGatewayPayments($result);
            $output->writeln(" - Listed result from Gateway " . sizeof($payments)." entries!");

            foreach ($payments as $payment) {
                foreach ($payment as $pay) {
                    $output->writeln("- SO_OM " . $pay->getReference()." entry found!");
                    $this->crm->saveWebhook2Crm([
                        'number_so_om' => $pay->getReference(),
                        'payment_id' => $pay->getInvoiceId(),
                        'pay_date' => $pay->getBillingDate(),
                    ]);
                    $output->writeln("- SO_OM " . $pay->getReference()." added to CRM!");
                }
            }

            $output->writeln(" Fin....... ");
            dd("Fin");
        } catch (\Exception $e) {
            $msg = "ERROR: ".$e->getMessage();
            $output->writeln($msg);
            \Log::error($msg);
        }

        return 0;
    }

    private function listGatewayPayments($resultOrderSalesPayment): array
    {
        $payments = [];
        foreach ($resultOrderSalesPayment as $item) {
            $result = $this->readPayment->findById($item->getFieldValue('otro_so'), 'mx_msk');
            if ($result->getResults()) {
                $payments[] = $result->getResults();
            }
        }

        return $payments;
    }

}
