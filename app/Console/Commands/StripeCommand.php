<?php

namespace App\Console\Commands;

use App\Clients\StripeClient;
use App\Clients\ZohoMskClient;
use App\Dtos\PaymentDto;
use App\Dtos\StripePaymentDto;
use App\Enums\GatewayEnum;
use App\Services\PaymentsMsk\CreatePaymentsMskService;
use App\Services\SalesOrders\ReadOrderSalesService;
use App\Services\Stripe\ReadPayment;
use App\Services\Webhooks\SaveWebhookZohoCrmService;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use zcrmsdk\crm\exception\ZCRMException;
use zcrmsdk\oauth\exception\ZohoOAuthException;

class StripeCommand extends Command
{
    protected string $name = 'stripe';

    protected string $signature = 'stripe';

    protected string $description = 'Connect with zoho crm and stripe, to update recent payments';

    private ReadOrderSalesService $service;
    private ReadPayment $readPayment;

    private SaveWebhookZohoCrmService $crm;
    private CreatePaymentsMskService $paymentService;

    private ReadPayment $stripe;

    private OutputInterface $output;

    public function __construct(
        ReadOrderSalesService    $service,
        ReadPayment              $readMercadoPago,
        CreatePaymentsMskService $mskService,
        StripeClient             $client
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
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->output = $output;
            $this->output->writeln(" Starting Stripe gateway retrieve data, please wait...");
            $paymentIntents = $this->retrievePaymentsFromGateway();
            $this->output->writeln(" Transforming Strip data 2 DTO..., keep waiting...");
            $payments = $this->payments2Dto($paymentIntents);
            $this->output->writeln(" Adding DTO 2 Mysql..., keep waiting...");
            $this->addPayments2Crm($payments);
            $this->output->writeln(" Finished....... ");
        } catch (\Exception $e) {
            $msg = "ERROR: " . $e->getMessage(). ' ' . $e->getLine();
            $this->output->writeln($msg);
            \Log::error($msg);
        }

        return 0;
    }

    /**
     * @throws ZohoOAuthException
     * @throws ZCRMException
     */
    private function addPayments2Crm(array $payments)
    {
        $i=1;
        /** @var PaymentDto $pay */
        foreach ($payments as $pay) {
            try {
                $this->output->writeln("$i - SO_OM " . $pay->getNumberSoOm() . " Prepare to save!");
                $this->crm->saveWebhook2Crm([
                    'number_so_om' => $pay->getNumberSoOm(),
                    'payment_id' => $pay->getPaymentId(),
                    'pay_date' => $pay->getPayDate(),
                    'id' => $pay->getId(),
                    'amount_charged' => (string)$pay->getAmountCharged(),
                    'origin' => $pay->getPaymentOrigin(),
                ]);
                $this->output->writeln("$i - saved in CRM");
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
                $this->output->writeln("$i - Saved Mysql!");
                $i++;
            } catch (\Exception $e) {
                $msg = "addPayments2Crm <-> ERROR: " . $e->getMessage(). ' ' . $e->getLine();
                $this->output->writeln($msg);
                \Log::error($msg);
            }
       }
    }

    public function retrievePaymentsFromGateway(): array
    {
        /** @var StripePaymentDto[] $paymentIntents */
        $paymentIntents = $this->stripe->findBySucceeded()->getResults();

        return array_reverse($paymentIntents);
    }

    /**
     * @throws ApiErrorException
     */
    public function payments2Dto($paymentIntents): array
    {
        $i=0;
        $payments = [];
        foreach ($paymentIntents as $payIntent) {
            $i++;
            $invoiceNumber = $payIntent->getInvoiceNumber();
            if (!$invoiceNumber) continue;
            $invoice = $this->stripe->findInvoiceByInvoiceId($invoiceNumber);
            $this->output->writeln("$i - PAID: " . $invoice->getNumberSoOm() . " " . $payIntent->getId(). ' - AMOUNT: '.$payIntent->getAmount());
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

        return $payments;
    }
}
