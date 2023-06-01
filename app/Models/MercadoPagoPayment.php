<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MercadoPagoPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'checkout_id',
        'checkout_url',
        'so',
        'sub_id',
        'event_id',
        'status',
        'status_detail',
        'date_approved',
        'send_crm',
    ];
}