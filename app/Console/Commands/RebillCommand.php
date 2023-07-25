<?php

namespace App\Console\Commands;

use App\Clients\RebillClient;

use App\Clients\ZohoMskClient;
use App\Dtos\PaymentDto;
use App\Dtos\StripePaymentDto;
use App\Enums\GatewayEnum;
use App\Services\PaymentsMsk\CreatePaymentsMskService;
use App\Services\Rebill\ReadPayment;
use App\Services\Rebill\ReadSubscription;
use App\Services\SalesOrders\ReadOrderSalesService;
use App\Services\Webhooks\SaveWebhookZohoCrmService;
use GuzzleHttp\Exception\GuzzleException;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\exception\ZCRMException;
use zcrmsdk\oauth\exception\ZohoOAuthException;

class RebillCommand extends Command
{
    protected string $name = 'rebill';

    protected string $signature = 'rebill';

    protected string $description = 'Connect with zoho crm and rebill, to update recent payments';

    private ReadOrderSalesService $service;

    private SaveWebhookZohoCrmService $crm;
    private CreatePaymentsMskService $paymentService;
    private ReadSubscription $rebill;

    private OutputInterface $output;

    public function __construct(
        ReadOrderSalesService $service,
        CreatePaymentsMskService $mskService,
        RebillClient $client
    ) {
        $this->setName($this->name);
        parent::__construct();
        $this->service = $service;
        $this->crm = new SaveWebhookZohoCrmService(new ZohoMskClient());
        $this->paymentService = $mskService;
        $this->rebill = new ReadSubscription($client);
    }

    protected function configure()
    {
        $this->addArgument('limit');
        $this->addArgument('page');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        try {
            $this->output->writeln(" Starting CRM retrieve orders, please wait...");
            $listOrderSalesCrm = $this->listOrderSalesCrm($input);
            $this->output->writeln(" Starting Rebill gateway retrieve data, please wait...");
            $rebillPayments = $this->getRebillPaymentsFromZohoOrders($listOrderSalesCrm);
            $this->output->writeln(" Transforming data 2 DTO..., keep waiting...");
            $payments = $this->payments2Dto($rebillPayments);
            $this->output->writeln(" Adding DTO 2 Mysql..., keep waiting...");
            $this->addPayments2Crm($payments);
            $this->output->writeln(" Finished....... ");
        } catch (\Exception|GuzzleException $e) {
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

    /**
     * @throws ZohoOAuthException
     */
    public function listOrderSalesCrm($input): array
    {
        $limit = $input->getArgument('limit');
        $page = $input->getArgument('page');

        return $this->service->listOrderSalesCrm($page, $limit);
    }

    /**
     * @throws GuzzleException
     */
    public function getRebillPaymentsFromZohoOrders($listOrderSalesCrm): array
    {
        /** @var ZCRMRecord $order */
        foreach ($listOrderSalesCrm as $order) {
            $subId = $this->getSubscriptionIdByZohoOrder($order);
            if ($subId) {
                $subscriptionArray = $this->rebill->findById($subId);
                $this->getRebillPaymentsFromSubArray($subscriptionArray);
                dd($subscriptionArray);
            }
        }
    }

    public function getRebillPaymentsFromSubArray($subArray): array
    {
        foreach ($subArray['invoices'] as $invoice) {
            dd($invoice['paidBags']);
        }
    }
    private function getSubscriptionIdByZohoOrder(ZCRMRecord $order)
    {
        $subscription = $order->getFieldValue('mp_subscription_id');
        if (!$subscription) {
            $subscription = $order->getFieldValue('stripe_subscription_id');
        } else {
            dd("FAILED TO GET SUB_ID", $order);
        }

        return $subscription ?? false;
    }
    /**
     * @throws ApiErrorException
     */
    public function payments2Dto($rebillPayments): array
    {
        $i=0;
        $payments = [];dd('kkkkk',$rebillPayments);
        foreach ($rebillPayments['data'] as $pay) {

            $i++;
            $invoiceNumber = $pay->getInvoiceNumber();
            if (!$invoiceNumber) continue;
            $invoice = $this->stripe->findInvoiceByInvoiceId($invoiceNumber);
            $this->output->writeln("$i - PAID: " . $invoice->getNumberSoOm() . " " . $pay->getId(). ' - AMOUNT: '.$pay->getAmount());
            $payments[] = new PaymentDto([
                'number_so_om' => $invoice->getNumberSoOm(),
                'payment_id' => $pay->getId(),
                'pay_date' => $pay->getPayDate(),
                'id' => $invoice->getInvoiceReference(),
                'amount_charged' => $pay->getAmount(),
                'sub_id' => $invoice->getSubscription(),
                'charge_id' => $pay->getId(),
                'contact_id' => $invoice->getCustomerId(),
                'contract_id' => null,
                'number_installment' => null,
                'fee' => $pay->getAmount() ,
                'payment_origin' => GatewayEnum::STRIPE,
                'external_number' => $invoice->getNumberSoOm(),
                'number_so' => null,
                'payment_date' => $pay->getPayDate(),
            ]);
        }

        return $payments;
    }
}
