<?php

namespace App\Interfaces;

interface IRead
{
    public function findById($id, $country = "");

    public function findBy($data);
}
