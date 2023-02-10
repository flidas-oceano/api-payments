<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'quantity',
        'price',
        'discount',
        'contract_id',
        'sku'
    ];
    private static $formAttributes = [
        'id',
        'quantity',
        'price',
        'discount',
        'contract_id',
        'sku'
    ];
    protected $table = 'products';
}
