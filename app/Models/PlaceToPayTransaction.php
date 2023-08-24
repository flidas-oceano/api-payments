<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaceToPayTransaction extends Model
{
    use HasFactory;

    protected $table = 'placetopay_transactions';
    public $timestamps = false;
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
    ];

}
