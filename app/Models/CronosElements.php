<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CronosElements extends Model
{
    use HasFactory;

    protected $table = 'cronos_elements';
    public $timestamps = false;

    protected $fillable = [
        'when_date',
        'so_number',
        'data',
        'type',
        'status',
        'processed',
        'log',
        'esanet',
        'error_lime_to_esanet',
        'sent_to_foc',
        'msk'
    ];




}