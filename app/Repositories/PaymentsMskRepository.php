<?php

namespace App\Repositories;

use App\Models\PaymentsMskModel;

class PaymentsMskRepository
{
    public function __construct()
    {
    }

    public function findBy($data)
    {
        $query = PaymentsMskModel::where('deleted_at', null);
        if (isset($data['contract_id'])) {
            $query->where('contract_id', $data['contract_id']);
        }
        $query->orderBy('id');
        $query->take(100);

        return $query->get();
    }
}
