<?php

namespace App\Services\PaymentsMsk;

use App\Factory\PaymentsMskFactory;

class CreatePaymentsMskService
{
    private PaymentsMskFactory $factory;
    private ListPaymentsMskService $service;

    public function __construct(
        PaymentsMskFactory $factory,
        ListPaymentsMskService $service
    ) {
        $this->factory = $factory;
        $this->service = $service;
    }

    public function create($data): ?\App\Models\PaymentsMskModel
    {
        $model = $this->service->findOneBy(['charge_id' => $data['charge_id']]);
        if (!$model) {
            $model = $this->factory->create($data);
            $model->save();
        }

        return $model;
    }
}
