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
            'id' => $invoiceDto->getId(),
            'fecha_emision' => $invoiceDto->getInvoiceDate(),
            "tipo_documento" => "FAC",
            "documento" => $invoiceDto->getInvoiceNumber(),
            "estado" => "P",
            "descripcion" => "FACTURA ".$invoiceDto->getInvoiceNumber(),
            "detalles"  => $invoiceDto->getProducts(),
            "subtotal_0" => $invoiceDto->getSubTotal(),
            "servicio" => $invoiceDto->getAdjust(),
            "iva" => $invoiceDto->getIva(),
            "ice" => $invoiceDto->getShipping(),
            "total" => $invoiceDto->getTotal(),

        ];
        $response = $this->client->post('/sistema/api/v1/documento', $body);

        return json_decode($response, true);
    }
}
