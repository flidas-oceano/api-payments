<?php

namespace App\Services\Contifico;

use App\Clients\ContificoClient;
use App\Interfaces\IRead;
use GuzzleHttp\Exception\GuzzleException;

class ReadInvoice implements IRead
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
    public function findById($id, $country = "")
    {
        $response = ($this->client->get('/sistema/api/v1/documento/'.$id))->getBody()->getContents();

        return json_decode($response, true);
    }

    /**
     * @throws GuzzleException
     * @throws \Exception
     */
    public function findBy($data)
    {
        $query = "&tipo_registro=CLI&tipo=FAC";
        if (isset($data['identification'])) {
            $query .= "&identificacion=".$data['identification'];
        }
        $response = $this->client->get('/sistema/api/v1/documento', $query);

        return json_decode($response->getBody()->getContents(), true);
    }
}
