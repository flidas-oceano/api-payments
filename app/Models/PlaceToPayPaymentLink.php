<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaceToPayPaymentLink extends Model
{
    use HasFactory;
    protected $table = 'placetopay_payment_links';
    protected $fillable = [
        'gateway',
        'type',
        'transactionId',
        'contract_so',
        'status',
        'rebill_customer_id',
        'country',
        'quotes'
    ];
    public function transaction()
    {
        return $this->belongsTo(PlaceToPayTransaction::class, 'transactionId');
    }

}
