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
        'contract_entity_id',
        'country',
        'quotes'
    ];
    public function transaction()
    {
        return $this->belongsTo(PlaceToPayTransaction::class, 'transactionId');
    }

    protected $status = [
        'REJECTED' => 'Pago Rechazado',
        'PENDING' => 'pending',
        'APPROVED' => 'Contrato Aprobado'
    ];
    public function setStatus($statusPay){
        //cuando este aprobado le sumo a la transaction un paid
        $this->update(['status' => $this->status[$statusPay]]);
    }

}

