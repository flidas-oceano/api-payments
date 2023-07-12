<?php

namespace App\Factory;

use App\Models\PaymentsMskModel;

class PaymentsMskFactory
{
    /**
     * @param $data
     * @param PaymentsMskModel|null $model
     * @return PaymentsMskModel|null
     */
    public function create($data, PaymentsMskModel $model = null): ?PaymentsMskModel
    {
        $date = new \DateTime();
        if (!$model) {
            $model = new PaymentsMskModel();
            $model->updated_at = ($date);
        }
        $model->created_at = ($date);

        if (isset($data['sub_id'])) {
            $model->sub_id = ($data['sub_id']);
        }
        if (isset($data['charge_id'])) {
            $model->charge_id = ($data['charge_id']);
        }
        if (isset($data['contact_id'])) {
            $model->contact_id = ($data['contact_id']);
        }
        if (isset($data['contract_id'])) {
            $model->contract_id = ($data['contract_id']);
        }
        if (isset($data['number_installment'])) {
            $model->number_installment = ($data['number_installment']);
        }
        if (isset($data['fee'])) {
            $model->fee = ($data['fee']);
        }
        if (isset($data['payment_origin'])) {
            $model->payment_origin = ($data['payment_origin']);
        }
        if (isset($data['external_number'])) {
            $model->external_number = ($data['external_number']);
        }
        if (isset($data['number_so'])) {
            $model->number_so = ($data['number_so']);
        }
        if (isset($data['number_so_om'])) {
            $model->number_so_om = ($data['number_so_om']);
        }
        if (isset($data['payment_date'])) {
            $payDate = \DateTime::createFromFormat('Y-m-d H:i:s', $data['payment_date']);
            $model->payment_date = ($payDate);
        }

        return $model;
    }
}
