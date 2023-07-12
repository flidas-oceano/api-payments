<?php

namespace App\Services\PaymentsMsk;

use App\Factory\PaymentsMskFactory;

class CreatePaymentsMskService
{
    /**
     * @var PaymentsMskFactory
     */
    private PaymentsMskFactory $factory;

    public function __construct(PaymentsMskFactory $factory)
    {
        $this->factory = $factory;
    }

    public function create($data): ?\App\Models\PaymentsMskModel
    {
        $model = $this->factory->create($data);
        $model->save();

        return $model;
    }
}
