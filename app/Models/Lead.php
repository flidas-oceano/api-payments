<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\{PurchasingProcess};

class Lead extends Model
{
    use HasFactory;
    protected $table = 'leads';
    protected $fillable = [
        'entity_id_crm',
        'lead_status',
        'source_lead',
        'lead_source',
        'name',
        'username',
        'email',
        'telephone',
        'method_contact',
        'contact_id',
        'method_contact_id',
        'profession',
        'speciality'
    ];
    public $timestamps = true;
    public $hidden = ['created_at','updated_at','source_lead','lead_status','lead_source','id'];

    public function purchasingProcesses(){
        $purchasingProcesses = $this->hasMany(PurchasingProcess::class,'lead_id','id');
        return $purchasingProcesses;
    }

}
