<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;
    protected $table = 'addresses';
    protected $fillable = [
        'type_of_address',
        'province_state',
        'postal_code',
        'country',
        'street',
        'locality',
    ];
    public $timestamps = false;
}
