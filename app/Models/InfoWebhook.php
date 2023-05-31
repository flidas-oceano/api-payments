<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InfoWebhook extends Model
{
    use HasFactory;
    protected $table = 'info_webhooks';

    protected $fillable = [
        'id',
        'json_data',
    ];
}
