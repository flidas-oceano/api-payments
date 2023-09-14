<?php

namespace App\Services\Contifico;

use App\Clients\ContificoClient;
use App\Dtos\Contifico\ContificoUserDto;
use App\Interfaces\IRead;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;

class WriteUser
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
    public function save(ContificoUserDto $userDto)
    {
        $body = [
            'id' => null,
            'tipo' => $userDto->getType(),
            "personaasociada_id" => null,
            'nombre_comercial' => $userDto->getTradeName(),
            'razon_social' => $userDto->getBusinessName(),
            'telefonos' => $userDto->getPhones(),
            'ruc' => $userDto->getRuc(),
            'cedula' => $userDto->getIdentificationCard(),
            'direccion' => $userDto->getAddress(),
            'porcentaje_descuento' => $userDto->getDiscountPercentage(),
            'email' => $userDto->getEmail(),
            'placa' => '',

            'adicional1_cliente' => $userDto->getIdentificationType(),
            'adicional2_cliente' => $userDto->getCurrency(),
            'adicional3_cliente' => $userDto->getTaxRate(),
            'adicional4_cliente' => '',
            "adicional1_proveedor" => null,
            "adicional2_proveedor" => null,
            "adicional3_proveedor" => null,
            "adicional4_proveedor" => null,

            'es_cliente' => $userDto->isCustomer(),
            'es_empleado' => $userDto->isEmployee(),
            'es_vendedor' => $userDto->isSeller(),
            'es_proveedor' => $userDto->isProvider(),
            'es_extranjero' => $userDto->isForeigner(),
        ];
        $response = $this->client->post('/sistema/api/v1/persona', $body);

        return json_decode($response, true);
    }
}
