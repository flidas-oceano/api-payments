<?php

namespace App\Services\Rebill;

use App\Clients\RebillClient;
use App\Interfaces\IReadPayment;
use GuzzleHttp\Exception\GuzzleException;

class ReadPayment implements IReadPayment
{
    private \GuzzleHttp\Client $request;

    public function __construct(RebillClient $client)
    {
        $this->request = $client->getClient();
    }

    /**
     * @throws GuzzleException
     */
    public function findById($id, $country = "")
    {
        $response = ($this->request->get('/v2/payments/'.$id))->getBody()->getContents();

        return json_decode($response, true);
    }
}
