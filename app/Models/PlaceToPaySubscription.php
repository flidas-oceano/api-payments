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
        'id',
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
        'expiration_date',
        'transactionId',
        'date_to_pay',
    ];
    private static $formAttributes = [
        'id',
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
        'expiration_date',
        'transactionId',
        'date_to_pay',
    ];
    public function transaction()
    {
        return $this->belongsTo(PlaceToPayTransaction::class, 'transactionId');
    }
}
