<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentLink extends Model
{
    use HasFactory;
    protected $table = 'payment_links';
    protected $fillable = ['gateway', 'type', 'contract_entity_id', 'contract_so', 'status', 'rebill_customer_id', 'country', 'quotes'];

}