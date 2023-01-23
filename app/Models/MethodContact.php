<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MethodContact extends Model
{
    use HasFactory;
    protected $table = 'method_contacts';
    protected $fillable = ['name'];
    public $timestamps = true;
    protected $hidden = ['created_at','updated_at'];

}
