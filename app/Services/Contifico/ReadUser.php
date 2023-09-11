<?php

namespace App\Services\Contifico;

use App\Clients\ContificoClient;
use App\Interfaces\IRead;
use GuzzleHttp\Exception\GuzzleException;

class ReadUser implements IRead
{
    private \GuzzleHttp\Client $request;

    public function __construct(ContificoClient $client)
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

    /**
     * @throws GuzzleException
     */
    public function findBy($data)
    {
        //$limit = $data['limit'] ?? 100;
        //$page = $data['page'] ?? 1;
        $query = "";
        if (isset($data['identification'])) {
            $query .= "&identificacion=".$data['identification'];
        }
        $response = ($this->request->get("/sistema/api/v1/persona?1=1".$query))->getBody()->getContents();

        return json_decode($response, true);
    }
}
