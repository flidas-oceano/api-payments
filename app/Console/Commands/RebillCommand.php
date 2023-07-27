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
                    'amount_charged' => (string) $pay->getAmountCharged(),
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
        $arrayReturn = [];
        $i=1;
        /** @var ZCRMRecord $order */
        foreach ($listOrderSalesCrm as $order) {
            $numSoOm = $order->getFieldValue('otro_so');
            $subId = $this->getSubscriptionIdByZohoOrder($order);
            if ($subId) {
                $subscriptionArray = $this->rebill->findById($subId);
                if ($subscriptionArray) {
                    $this->output->writeln("$i - $numSoOm: Belongs to Rebill");
                    $response = $this->getRebillPaymentsFromSubArray($subscriptionArray);
                    $arrayReturn[] = [
                        'order' => $order,
                        'data' => $response,
                    ];
                } else {
                    $this->output->writeln("$i - $numSoOm: Does not belong to Rebill");
                }
            }
            $i++;
        }

        return $arrayReturn;
    }

    public function getRebillPaymentsFromSubArray($subArray): array
    {
        $response = [];
        foreach ($subArray['invoices'] as $invoice) {
            foreach ($invoice['paidBags'] as $paidBag) {
                $payment = $paidBag['payment'];
                if ($payment['status'] == 'SUCCEEDED') {
                    $payment['extra']['invoice_id'] = $invoice['id'];
                    $payment['extra']['customer_id'] = $invoice['buyer']['customer']['id'];
                    $payment['extra']['customer_email'] = $invoice['buyer']['customer']['userEmail'];
                    $response[] = $payment;
                }
            }
        }

        return $response;
    }
    private function getSubscriptionIdByZohoOrder(ZCRMRecord $order)
    {
        $subscription = $order->getFieldValue('mp_subscription_id');
        if (!$subscription) {
            $subscription = $order->getFieldValue('stripe_subscription_id');
        }

        return $subscription ?? false;
    }
    /**
     * @throws ApiErrorException
     */
    public function payments2Dto($rebillPayments): array
    {
        $i=0;
        $payments = [];
        foreach ($rebillPayments as $pay) {
            $i++;
            /** @var ZCRMRecord $order */
            $order = $pay['order'];
            foreach ($pay['data'] as $payment) {
                $payDate = substr($payment['createdAt'], 0, 10);
                $payments[] = new PaymentDto([
                    'id' => $payment['extra']['invoice_id'],
                    'number_so_om' => $order->getFieldValue('otro_so'),
                    'payment_id' => $payment['id'],
                    'pay_date' => $payDate,
                    'amount_charged' => $payment['amount'],
                    'sub_id' => $this->getSubscriptionIdByZohoOrder($order),
                    'charge_id' => $payment['id'],
                    'contact_id' => $payment['extra']['customer_id'],
                    'contract_id' => null,
                    'number_installment' => null,
                    'fee' => $payment['amount'],
                    'payment_origin' => GatewayEnum::REBILL,
                    'external_number' => $payment['extra']['invoice_id'],
                    'number_so' => $order->getFieldValue('SO_Number'),
                    'payment_date' => $payDate,
                ]);
            }
        }

        return $payments;
    }
}
