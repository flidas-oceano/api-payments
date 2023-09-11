<?php

namespace App\Services\Contifico;

use App\Clients\ContificoClient;
use App\Dtos\Contifico\ContificoUserDto;
use App\Interfaces\IRead;
use GuzzleHttp\Exception\GuzzleException;

class WriteUser
{
    private \GuzzleHttp\Client $request;

    public function __construct(ContificoClient $client)
    {
        $this->request = $client->getClient();
    }

    /**
     * @throws GuzzleException
     */
    public function save(ContificoUserDto $userDto)
    {
        $response = ($this->request->post('/sistema/api/v1/persona', [
            'tipo' => $userDto->getType(),
            'nombre_comercial' => $userDto->getTradeName(),
            'razon_social' => $userDto->getBusinessName(),
            'telefonos' => $userDto->getPhones(),
            'ruc' => $userDto->getDocument(),
            'cedula' => $userDto->getDocument2(),
            'direccion' => $userDto->getAddress(),
            'porcentaje_descuento' => $userDto->getDiscountPercentage(),
            'email' => $userDto->getEmail(),
            'adicional1_cliente' => $userDto->getIdentificationType(),
            'adicional2_cliente' => $userDto->getCurrency(),
            'adicional3_cliente' => $userDto->getTaxRate(),

            'es_cliente' => $userDto->isCustomer(),
            'es_empleado' => $userDto->isEmployee(),
            'es_vendedor' => $userDto->isSeller(),
            'es_proveedor' => $userDto->isProvider(),
            'es_extranjero' => $userDto->isForeigner(),
        ]))->getBody()->getContents();dd($response);

        return json_decode($response, true);
    }
}
