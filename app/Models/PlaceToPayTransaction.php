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
        'status',
        'reason',
        'message',
        'date',
        'requestId',
        'processUrl',
        'contact_id',
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
    ];
    private static $formAttributes = [
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
    ];
    public function subscriptions()
    {
        return $this->hasMany(PlaceToPaySubscription::class, 'transactionId');
    }
}
