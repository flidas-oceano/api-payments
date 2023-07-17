<?php

namespace App\Services\PaymentsMsk;

use App\Factory\PaymentsMskFactory;
use App\Repositories\PaymentsMskRepository;

class ListPaymentsMskService
{
    private PaymentsMskRepository $repository;

    public function __construct(PaymentsMskRepository $repository)
    {
        $this->repository = $repository;
    }

    public function findBy($data = [])
    {
        return $this->repository->findBy($data);
    }

    public function findOneBy($data = [])
    {
        return $this->repository->findOneBy($data);
    }
}
