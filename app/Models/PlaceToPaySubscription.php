<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaceToPaySubscription extends Model
{
    use HasFactory;
    protected $table = 'placetopay_subscriptions';
    public $timestamps = false;
    protected $primaryKey = 'id';

    public $fillable = [
        'requestIdFather',
        'status',
        'reason',
        'message',
        'date',
        'requestId',
        'contact_id',
        'authorization',
        'total',
        'currency',
        'nro_quote',
        'reference',
        'type',
        'token_collect_para_el_pago',
        'expiration_date',
        'transactionId'
    ];
    private static $formAttributes = [

        'requestIdFather',
        'status',
        'reason',
        'message',
        'date',
        'requestId',
        'contact_id',
        'authorization',
        'total',
        'currency',
        'reference',
        'type',
        'token_collect_para_el_pago',
        'expiration_date',
        'transactionId'
    ];
    public function transaction()
    {
        return $this->belongsTo(PlaceToPayTransaction::class, 'transactionId');
    }
}
