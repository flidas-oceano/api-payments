<?php

namespace App\Clients;

use App\Interfaces\IClient;

class ContificoClient implements IClient
{
    private \GuzzleHttp\Client $client;

    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client(['base_uri' => 'https://api.contifico.com',
            'headers' => [
                'accept' => 'application/json',
                'authorization' => env('CONTIFICO_APIK'),
            ],
        ]);
    }

    public function getClient(): \GuzzleHttp\Client
    {
        return $this->client;
    }
}
