<?php

namespace App\Services\Rebill;

use App\Clients\RebillClient;
use App\Interfaces\IRead;
use GuzzleHttp\Exception\GuzzleException;

class ReadSubscription implements IRead
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
        try {
            $response = ($this->request->get('/v2/subscriptions/' . $id))->getBody()->getContents();
            \Log::info("$id belongs to rebill!", []);

            return json_decode($response, true);
        } catch (\Exception $e) {
            \Log::debug("$id does not belong to rebill!", []);
            return false;
        }
    }

    public function findBy($data)
    {
        $limit = $data['limit'] ?? 100;
        $page = $data['page'] ?? 1;
        $response = ($this->request->get("/v2/subscriptions?status=SUCCEEDED&take=$limit&page=$page"))->getBody()->getContents();

        return json_decode($response, true);
    }
}
