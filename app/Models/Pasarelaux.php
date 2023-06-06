<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// php artisan make:model PasarelaAuth -mc

class Pasarelaux extends Model
{
    use HasFactory;

    protected $table = 'pasarelaux';
    public $timestamps = false;
    protected $primaryKey = 'sale_id';

    protected $fillable = [
        'sale_id', 'data'
    ];
}
