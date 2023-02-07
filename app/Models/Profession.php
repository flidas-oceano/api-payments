<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profession extends Model
{
    use HasFactory;
    protected $timestamp = true;
    protected $fillable = ['id','name'];
    protected $hidden = ['created_at','updated_at'];
    
    public function getName(){
        $name = $this->name;
        return $name;
    }
}
