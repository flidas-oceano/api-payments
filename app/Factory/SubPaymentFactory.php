<?php

namespace App\Factory;

use App\Models\SubPaymentsRegistryModel;

class SubPaymentFactory
{
    /**
     * @param $data
     * @param SubPaymentsRegistryModel|null $model
     * @return SubPaymentsRegistryModel|null
     */
    public function create($data, SubPaymentsRegistryModel $model = null): ?SubPaymentsRegistryModel
    {
        $date = new \DateTime();
        if (!$model) {
            $model = new SubPaymentsRegistryModel();
            $model->updated_at = ($date);
        }
        $model->created_at = ($date);

        if (isset($data['number_so_om'])) {
            $model->number_so_om = ($data['number_so_om']);
        }
        if (isset($data['amount'])) {
            $model->amount = ($data['amount']);
        }
        if (isset($data['payment_id'])) {
            $model->payment_id = ($data['payment_id']);
        }
        if (isset($data['pay_date'])) {
            $payDate = \DateTime::createFromFormat('Y-m-d H:i:s', $data['pay_date']);
            $model->pay_date = ($payDate);
        }
        if (isset($data['pay_state'])) {
            $model->pay_state = ($data['pay_state']);
        }
        if (isset($data['gateway'])) {
            $model->gateway = ($data['gateway']);
        }

        return $model;
    }
}
