<?php

namespace App\Services\MercadoPago;

class Credentials
{
    public static function getCredentials($country)
    {
        switch($country)
        {
            case 'mx_msk': return('APP_USR-6404915214202963-041914-6248701ac1af4c59715f9408b68db885-1350977988');
        }
    }
}
