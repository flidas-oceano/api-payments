<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SourceLead extends Model
{
    use HasFactory;
    protected $table = 'sources_lead';
    protected $timestamp = true;
    protected $fillable = ['id', 'name'];
    protected $hidden = ['created_at', 'updated_at'];

    public function getName()
    {
        $name = $this->name;
        return $name;
    }
}