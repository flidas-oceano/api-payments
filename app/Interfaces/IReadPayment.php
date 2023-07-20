<?php

namespace App\Interfaces;

interface IReadPayment
{
    public function findById($id, $country);
}
