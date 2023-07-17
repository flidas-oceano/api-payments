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
        $query = $this->search($data);
        $query->take(100);

        return $query->get();
    }
    public function findOneBy($data)
    {
        $query = $this->search($data);

        return $query->first();
    }
    private function search($data)
    {
        $query = PaymentsMskModel::where('deleted_at', null);
        if (isset($data['contract_id'])) {
            $query->where('contract_id', $data['contract_id']);
        }
        if (isset($data['charge_id'])) {
            $query->where('charge_id', $data['charge_id']);
        }
        $query->orderBy('id');

        return $query;
    }
}
