<?php

namespace App\Console\Commands;

use App\Clients\ZohoMskClient;
use App\Dtos\MpResultDto;
use Illuminate\Support\Facades\Log;
use zcrmsdk\crm\exception\ZCRMException;
use App\Services\MercadoPago\ReadPayment;
use Symfony\Component\Console\Command\Command;
use zcrmsdk\oauth\exception\ZohoOAuthException;
use App\Services\SalesOrders\ReadOrderSalesService;
use Symfony\Component\Console\Input\InputInterface;
use App\Services\Webhooks\SaveWebhookZohoCrmService;
use Symfony\Component\Console\Output\OutputInterface;

class ConnectOrderSalesWithCrmCommand extends Command
{
    protected string $name = 'sales-order:crm {limit=10&page=1}';

    protected string $signature = 'sales-order:crm {limit=10&page=1}';

    protected string $description = 'Connect with zoho crm and mercado pago, to update recent payments';

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

    protected function configure()
    {
        $this->addArgument('limit');
        $this->addArgument('page');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $limit = $input->getArgument('limit');
            $page = $input->getArgument('page');
            $output->writeln(" - Executing " . __CLASS__ . " " . $page . " " . $limit);
            $result = $this->service->listOrderSalesCrm($page, $limit);
            if (!(sizeof($result) > 0)) {
                $output->writeln(" - no entries result from CRM, aborting...");
                return 0;
            }
            $output->writeln(" - Listed result from CRM " . sizeof($result) . " entries!");
            $payments = $this->listGatewayPayments($result);
            $output->writeln(" - Listed result from Gateway " . sizeof($payments) . " entries!");
            $this->addPayments2Crm($payments, $output);
            $output->writeln(" Fin....... ");
        } catch (\Exception $e) {
            $msg = "ERROR: " . $e->getMessage();
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
        Log::info(print_r($payments, true));
        return $payments;
    }

    /**
     * @throws ZohoOAuthException
     * @throws ZCRMException
     */
    private function addPayments2Crm(array $payments, OutputInterface $output)
    {
        foreach ($payments as $payment) {
            /** @var MpResultDto $pay */
            foreach ($payment as $pay) {
                $output->writeln("- SO_OM " . $pay->getReference() . " entry found!");
                $this->crm->saveWebhook2Crm([
                    'number_so_om' => $pay->getReference(),
                    'payment_id' => $pay->getInvoiceId(),
                    'pay_date' => $pay->getBillingDate(),
                    'id' => $pay->getId(),
                    'amount_charged' => $pay->getAmountCharged(),
                ]);
                $output->writeln("- SO_OM " . $pay->getReference() . " added to CRM!");
            }
        }
    }
}
