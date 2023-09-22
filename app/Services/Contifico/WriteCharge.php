<?php

namespace App\Services\Contifico;

use App\Clients\ContificoClient;
use App\Dtos\Contifico\ContificoInvoiceChargeDto;
use App\Dtos\Contifico\ContificoUserDto;
use App\Interfaces\IRead;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;

class WriteCharge
{
    private \GuzzleHttp\Client $request;
    private ContificoClient $client;

    public function __construct(ContificoClient $client)
    {
        $this->client = $client;
        $this->request = $client->getClient();
    }

    /**
     * @throws GuzzleException
     * @throws \Exception
     */
    public function save(ContificoInvoiceChargeDto $chargeDto)
    {
        $body = [
            'id' => $chargeDto->getId(),
            'tipo_ping' => $chargeDto->getChargeType(),
            'forma_cobro' => $chargeDto->getChargeMethod(),
            'monto' => $chargeDto->getValueCharged(),
            'fecha' => $chargeDto->getDate(),

        ];
        $response = $this->client->post('/sistema/api/v1/documento/'.$chargeDto->getInvoiceId().'/cobro', $body);

        return json_decode($response, true);
    }
}
