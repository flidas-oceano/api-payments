<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StripePurchase extends Model
{
    use HasFactory;
    protected $fillable = ['sub_id','contract_id','installment_amount','quotes'];
}
