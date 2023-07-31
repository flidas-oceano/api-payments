<?php

namespace App\Services\MercadoPago;

use App\Dtos\MpSearchDto;
use App\Interfaces\IRead;
use App\Interfaces\ISearchDto;

class ReadPayment implements IRead
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

    public function findBy($data)
    {
        // TODO: Implement findBy() method.
    }
}
