<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
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
        'contact_id',
    ];
    protected $table = 'contract';
}
