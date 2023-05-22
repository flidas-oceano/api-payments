<?php

namespace App\Services\Webhooks;

use App\Factory\SubPaymentFactory;

class CreateSubPaymentsRegistryService
{
    /**
     * @var SubPaymentFactory
     */
    private SubPaymentFactory $factory;

    public function __construct(SubPaymentFactory $factory)
    {
        $this->factory = $factory;
    }

    public function create($data)
    {
        $model = $this->factory->create($data);
        $model->save();

        return $model;
    }
}
