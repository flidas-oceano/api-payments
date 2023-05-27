<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Webhooks extends Model
{
    use HasFactory;

    protected $table = 'webhooks';
    public $timestamps = false;
    protected $primaryKey = 'id';

    protected $fillable = [
        'moment', 'type', 'country', 'event_id','so', 'status'
    ];
}
