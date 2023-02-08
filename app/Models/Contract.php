<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'contact_id',
        'installments',
        'Fecha_de_Vto',
        'lead_source',
        'name',
        'address',
        'payment_type',
        'country',
        'is_sub',
        'payment_in_advance',
        'left_installments',
        'left_payment_type',
        'currency',
        'left_payment_type',
    ];
    protected $table = 'contract';
}
