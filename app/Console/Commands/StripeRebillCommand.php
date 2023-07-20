<?php

namespace App\Console\Commands;

use App\Clients\StripeClient;
use App\Clients\ZohoMskClient;
use App\Dtos\MpResultDto;
use App\Dtos\PaymentDto;
use App\Dtos\StripePaymentDto;
use App\Enums\GatewayEnum;
use App\Services\PaymentsMsk\CreatePaymentsMskService;
use App\Services\Stripe\ReadPayment;
use Illuminate\Support\Facades\Log;
use zcrmsdk\crm\exception\ZCRMException;
use Symfony\Component\Console\Command\Command;
use zcrmsdk\oauth\exception\ZohoOAuthException;
use App\Services\SalesOrders\ReadOrderSalesService;
use Symfony\Component\Console\Input\InputInterface;
use App\Services\Webhooks\SaveWebhookZohoCrmService;
use Symfony\Component\Console\Output\OutputInterface;

class StripeRebillCommand extends Command
{
    protected string $name = 'stripe';

    protected string $signature = 'stripe';

    protected string $description = 'Connect with zoho crm and stripe/Rebill, to update recent payments';

    private ReadOrderSalesService $service;
    private ReadPayment $readPayment;

    private SaveWebhookZohoCrmService $crm;
    private CreatePaymentsMskService $paymentService;

    private ReadPayment $stripe;

    public function __construct(
        ReadOrderSalesService $service,
        ReadPayment $readMercadoPago,
        CreatePaymentsMskService $mskService,
        StripeClient $client
    ) {
        $this->setName($this->name);
        parent::__construct();
        $this->service = $service;
        $this->readPayment = $readMercadoPago;
        $this->crm = new SaveWebhookZohoCrmService(new ZohoMskClient());
        $this->paymentService = $mskService;
        $this->stripe = new ReadPayment($client);
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

            /** @var StripePaymentDto[] $paymentIntents */
            $paymentIntents = $this->stripe->findBySucceeded()->getResults();
            $i=0;
            $payments = [];
            foreach ($paymentIntents as $payIntent) {
                $i++;
                $invoiceNumber = $payIntent->getInvoiceNumber();
                if (!$invoiceNumber) continue;
                $invoice = $this->stripe->findInvoiceByInvoiceId($invoiceNumber);
                $output->writeln("$i - PAID: " . $invoice->getNumberSoOm() . " " . $payIntent->getId(). ' - AMOUNT: '.$payIntent->getAmount());
                $payments[] = new PaymentDto([
                    'number_so_om' => $invoice->getNumberSoOm(),
                    'payment_id' => $payIntent->getId(),
                    'pay_date' => $payIntent->getPayDate(),
                    'id' => $invoice->getInvoiceReference(),
                    'amount_charged' => $payIntent->getAmount(),
                    'sub_id' => $invoice->getSubscription(),
                    'charge_id' => $payIntent->getId(),
                    'contact_id' => $invoice->getCustomerId(),
                    'contract_id' => null,
                    'number_installment' => null,
                    'fee' => $payIntent->getAmount() ,
                    'payment_origin' => GatewayEnum::STRIPE,
                    'external_number' => $invoice->getNumberSoOm(),
                    'number_so' => null,
                    'payment_date' => $payIntent->getPayDate(),
                ]);
            }
            $this->addPayments2Crm($payments, $output);

            $output->writeln(" Fin....... ");
        } catch (\Exception $e) {
            $msg = "ERROR: " . $e->getMessage();
            $output->writeln($msg);
            \Log::error($msg);
        }

        return 0;
    }

    /**
     * @throws ZohoOAuthException
     * @throws ZCRMException
     */
    private function addPayments2Crm(array $payments, OutputInterface $output)
    {
        $i=1;
        /** @var PaymentDto $pay */
        foreach ($payments as $pay) {
            $output->writeln("$i - SO_OM " . $pay->getNumberSoOm() . " save 2 CRM!");
            $this->crm->saveWebhook2Crm([
                'number_so_om' => $pay->getNumberSoOm(),
                'payment_id' => $pay->getPaymentId(),
                'pay_date' => $pay->getPayDate(),
                'id' => $pay->getId(),
                'amount_charged' => (string) $pay->getAmountCharged(),
                'origin' => $pay->getPaymentOrigin(),
            ]);
            $output->writeln("$i - SO_OM " . $pay->getNumberSoOm() . " save 2 Mysql!");
            $this->paymentService->create([
                'sub_id' => $pay->getSubId(),
                'charge_id' => $pay->getChargeId(),
                'contact_id' => $pay->getContactId(),
                'contract_id' => '',
                'number_installment' => $i,
                'fee' => $pay->getAmountCharged(),
                'payment_origin' => $pay->getPaymentOrigin(),
                'external_number' => $pay->getId(),
                'number_so' => null,
                'number_so_om' => $pay->getNumberSoOm(),
                'payment_date' => $pay->getPayDate(),
            ]);
            $i++;
            $output->writeln("$i - SO_OM " . $pay->getNumberSoOm() . " Finish!");
       }
    }
}
