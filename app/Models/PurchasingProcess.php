<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\{LeadProcess};


class PurchasingProcess extends Model
{
    use HasFactory;

    protected $table = 'purchasing_proccess';
    protected $fillable = ['lead_id_fk'];

    public function leads(){
        $leads = $this->hasOne(Lead::class,'id','lead_id_fk');
        return $leads;
    }
}
