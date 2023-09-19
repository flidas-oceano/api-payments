<?php

namespace App\Services\Contifico;

use App\Clients\ContificoClient;
use App\Dtos\Contifico\ContificoInvoiceDetailsDto;
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
            "estado" => $invoiceDto->getStatus(),
            "descripcion" => "FACTURA ".$invoiceDto->getInvoiceNumber(),
            "detalles"  => $this->getDetails($invoiceDto->getProducts()),
            "subtotal_0" => $invoiceDto->getSubTotal(),
            "subtotal_12" => $invoiceDto->getSubTotalIva(),
            "autorizacion" => $invoiceDto->getAuthorization(),
            "servicio" => $invoiceDto->getAdjust(),
            "iva" => $invoiceDto->getIva(),
            "ice" => $invoiceDto->getShipping(),
            "total" => $invoiceDto->getTotal(),
            "cliente" => [
                "ruc"=> $invoiceDto->getCustomer()->getRuc(),
                "cedula"=> $invoiceDto->getCustomer()->getIdentificationCard(),
                "razon_social"=> $invoiceDto->getCustomer()->getBusinessName(),
                "telefonos"=> $invoiceDto->getCustomer()->getPhones(),
                "direccion"=> $invoiceDto->getCustomer()->getAddress(),
                "tipo"=> "N",
                "email"=> $invoiceDto->getCustomer()->getEmail(),
                "es_extranjero"=> false
            ],
            'pos' => env('CONTIFICO_APIT'),
        ];
        $response = $this->client->post('/sistema/api/v1/documento', $body);

        return json_decode($response, true);
    }

    /**
     * @param ContificoInvoiceDetailsDto[] $products
     * @return array
     */
    private function getDetails(array $products): array
    {
        $response = [];
        foreach ($products as $product) {
            $response[] = [
                'producto_id' => $product->getProductId(),
                'cantidad' => $product->getQuantity(),
                'precio' => $product->getPrice(),
                'porcentaje_iva' => $product->getPercentageIva(),
                'porcentaje_descuento' => $product->getPercentageDiscount(),
                'base_cero' => $product->getZeroBase(),
                'base_gravable' => $product->getRecordableBase(),
                'base_no_gravable' => $product->getNonRecordableBase(),
            ];
        }
        return $response;
    }
}
