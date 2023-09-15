<?php

namespace App\Services\Contifico;

use App\Clients\ContificoClient;
use App\Dtos\Contifico\ContificoInvoiceDto;
use App\Dtos\Contifico\ContificoUserDto;
use App\Interfaces\IRead;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;

class WriteInvoice
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
    public function save(ContificoInvoiceDto $invoiceDto)
    {
        $body = [
            'id' => null,
        ];
        $response = $this->client->post('/sistema/api/v1/documento', $body);

        return json_decode($response, true);
    }
}
