<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaceToPayTransaction extends Model
{
    use HasFactory;

    protected $table = 'placetopay_transactions';
    public $timestamps = true;
    protected $primaryKey = 'id';

    public $fillable = [
        'id',
        'status',
        'reason',
        'message',
        'date',
        'requestId',
        'processUrl',
        'contact_id',
        'lead_id',
        'authorization',
        'total',
        'currency',
        'reference',
        'type',
        'token_collect_para_el_pago',
        'expiration_date',
        'remaining_installments',
        'first_installment',
        'quotes',
        'installments_paid',

    ];
    private static $formAttributes = [
        'id',
        'requestId',
        'processUrl',
        'contact_id',
        'authorization',
        'total',
        'currency',
        'reference',
        'type',
        'token_collect_para_el_pago',
        'status',
        'reason',
        'message',
        'date',
        'expiration_date',
        'remaining_installments',
        'first_installment',
        'quotes',
        'installments_paid',
    ];
    function isSubscription() {
        return ($this->type === 'requestSubscription') ? true: false;
    }
    function isAdvancedSubscription() {
        return $this->first_installment !== null;
    }
    function installmentsToPay() {
        $diferencia = $this->quotes -  $this->installments_paid;
        return $diferencia;
    }
    public function subscriptions()
    {
        return $this->hasMany(PlaceToPaySubscription::class, 'transactionId');
    }
    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }
}
