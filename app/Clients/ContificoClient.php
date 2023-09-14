<?php

namespace App\Clients;

use App\Interfaces\IClient;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class ContificoClient implements IClient
{
    private \GuzzleHttp\Client $client;

    const URL = 'https://api.contifico.com';
    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client(['base_uri' => self::URL,
            'headers' => $this->getHeaders()
        ]);
    }

    public function getClient(): \GuzzleHttp\Client
    {
        return $this->client;
    }

    public function getHeaders(): array
    {
        return [
            'accept' => 'application/json',
            'Content-type' => 'application/json; charset-utf-8',
            'authorization' => env('CONTIFICO_APIK'),
        ];
    }

    /**
     * @throws \Exception
     */
    public function post($uri, $body)
    {
        try {
            $request = new Request(
                'POST',
                    self::URL . $uri . '/?pos='.env('CONTIFICO_APIT'),
                $this->getHeaders(),
                json_encode($body)
            );
            $client = new Client();

            return $client->sendAsync($request)->wait();
        } catch (\Exception $e) {
            $posA = strpos($e->getMessage(), '{');
            $posB = strpos($e->getMessage(), '}');
            $resp = substr($e->getMessage(), $posA, $posB);

            throw new \Exception($resp);
        }
    }

    /**
     * @throws \Exception
     */
    public function get($uri, $query = "")
    {
        try {
            $request = new Request(
                'GET',
                self::URL . $uri . '/?pos='.env('CONTIFICO_APIT').$query,
                $this->getHeaders(),
            );
            $client = new Client();

            return $client->sendAsync($request)->wait();
        } catch (\Exception $e) {
            $posA = strpos($e->getMessage(), '{');
            $posB = strpos($e->getMessage(), '}');
            $resp = substr($e->getMessage(), $posA, $posB);

            throw new \Exception($resp);
        }
    }
}
