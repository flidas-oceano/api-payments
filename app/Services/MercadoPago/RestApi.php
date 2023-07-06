<?php

namespace App\Services\MercadoPago;

use Illuminate\Support\Facades\Http;

class RestApi
{
    public function searchByReference($reference, $country)
    {
        return $this->restCall(Credentials::getCredentials($country), 'external_reference', $reference);
    }

    public function restCall($key, $nameColumn, $valueColumn, $limit = 1000)
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $key
        ])->get('https://api.mercadopago.com/v1/payments/search', [
            'limit' => $limit,
            $nameColumn => $valueColumn
        ]);
    }
}
