<?php

namespace App\Services\MercadoPago;
use App\Dtos\MpSearchDto;

class ReadPayment
{
    private RestApi $api;

    public function __construct(RestApi $api)
    {
        $this->api = $api;
    }
    public function findById($id, $country): MpSearchDto
    {
        $data = $this->api->searchByReference('x'.$id, $country)->json();
        //if ($data['paging']['total'] > 0)
            //dd(json_encode($data));
        return new MpSearchDto($data);
    }
}
