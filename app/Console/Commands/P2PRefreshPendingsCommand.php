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
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\exception\ZCRMException;
use zcrmsdk\oauth\exception\ZohoOAuthException;

class P2PRefreshPendingsCommand extends Command
{
    protected string $name = 'p2p:refresh-pendings';

    protected string $signature = 'p2p:refresh-pendings';

    protected string $description = 'Busca los estados de sesiones y cuotas en pending y opera';

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
            $this->output->writeln("Comienzo - Ejecutando cobros P2P RefreshPendings manual");
            /** @var PlaceToPayService $placeToPayService */
            $placeToPayService = App::make(PlaceToPayService::class);
            $result = $placeToPayService->refreshPendings();
            dd($result);
            // Log::channel('slack')->info(json_encode($result, JSON_PRETTY_PRINT));
            $this->output->writeln("Fin - Ejecutando cobros P2P RefreshPenndigs manual");

        } catch (\Exception|GuzzleException $e) {
            $msg = "ERROR: " . $e->getMessage(). ' ' . $e->getLine();
            $this->output->writeln($msg);

            Log::error($msg);
            Log::channel('slack')->error($msg, ['exception' => $e]);
        }

        return 0;
    }

}
