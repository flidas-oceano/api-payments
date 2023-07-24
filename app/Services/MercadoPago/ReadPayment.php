<?php

namespace App\Services\MercadoPago;

use App\Dtos\MpSearchDto;
use App\Interfaces\IReadPayment;
use App\Interfaces\ISearchDto;

class ReadPayment implements IReadPayment
{
    private RestApi $api;

    public function __construct(RestApi $api)
    {
        $this->api = $api;
    }
    public function findById($id, $country = ""): ISearchDto
    {
        $data = $this->api
            ->searchByReference('x' . $id, $country)
            ->json();

        return new MpSearchDto($data);
    }
}
