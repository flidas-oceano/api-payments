<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentsMskModel extends Model
{
    use HasFactory;

    protected $table = 'payments_msk';
    public $timestamps = false;
    protected $primaryKey = 'id';

    protected $fillable = [
        'sub_id', 'charge_id', 'contact_id', 'contract_id', 'fee', 'number_installment','payment_origin', 'external_number', 'number_so', 'number_so_om', 'payment_date'
    ];
}
