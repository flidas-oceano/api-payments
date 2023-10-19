<?php

namespace App\Console\Commands;

use App\Clients\RebillClient;
use App\Clients\ZohoClient;
use App\Clients\ZohoMskClient;
use App\Dtos\PaymentDto;
use App\Dtos\StripePaymentDto;
use App\Enums\GatewayEnum;
use App\Services\PaymentsMsk\CreatePaymentsMskService;
use App\Services\PlaceToPay\PlaceToPayService;
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

class P2PCommand extends Command
{
    protected string $name = 'p2p:stage';

    protected string $signature = 'p2p:stage';

    protected string $description = 'Connect with zoho crm and rebill, to update recent payments';

    protected PlaceToPayService $p2pService;

    private OutputInterface $output;

    public function __construct(
        ZohoClient $client,
        PlaceToPayService $p2pService
    ) {
        $this->setName($this->name);
        parent::__construct();
        $this->p2pService = $p2pService;
    }

    protected function configure()
    {
        //$this->addArgument('limit');
        //$this->addArgument('page');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        try {
            $this->output->writeln("funciona");
            /** @var PlaceToPayService $placeToPayService */
            $placeToPayService = \App::make(PlaceToPayService::class); // Instancia el servicio
            $placeToPayService->stageOne();
            $this->output->writeln("fin");

        } catch (\Exception|GuzzleException $e) {
            $msg = "ERROR: " . $e->getMessage(). ' ' . $e->getLine();
            $this->output->writeln($msg);
            \Log::error($msg);
        }

        return 0;
    }

}
