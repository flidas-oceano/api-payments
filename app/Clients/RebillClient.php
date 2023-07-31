<?php

namespace App\Clients;

use App\Interfaces\IClient;
use Rebill\SDK\Models\GatewayStripe;

class RebillClient implements IClient
{
    private \GuzzleHttp\Client $rebill;

    public function __construct()
    {
        $this->rebill = new \GuzzleHttp\Client(['base_uri' => 'https://api.rebill.to',
            'headers' => [
                'accept' => 'application/json',
                'authorization' => 'Bearer '.env('REBILL_APIK'),
            ],
        ]);
    }

    public function getClient(): \GuzzleHttp\Client
    {
        return $this->rebill;
    }
}
