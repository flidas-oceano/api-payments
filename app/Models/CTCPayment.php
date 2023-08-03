<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CTCPayment extends Model
{
    use HasFactory;
    protected $table = 'ctc_payments';
    protected $fillable = [
        'folio_pago',
        'folio_suscripcion',
        'so_contract',
        'quotes'
    ];
    public $timestamps = true;
    public $hidden = ['created_at', 'updated_at'];
   
}
