<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class SubPaymentsRegistryModel extends Model
{
    use HasFactory, Notifiable;

    protected $table = "sub_payments_registry";

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'amount',
        'payment_id',
        'pay_date',
        'pay_state',
        'gateway'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];


}
