<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\{PurchasingProcess,Contact};

class Lead extends Model
{
    use HasFactory;
    protected $table = 'leads';
    protected $fillable = ['entity_id_crm','name','username','telephone','lead_status','source_lead','lead_source','method_contact_id_fk','contact_id_fk','addresses_id_fk'];

    public function purchasingProcesses(){
        $purchasingProcesses = $this->hasMany(PurchasingProcess::class,'lead_id_fk','id');
        return $purchasingProcesses;
    }
    public function contact(){
        $contact = $this->hasOne(Contact::class,'id','contact_id_fk');
        return $contact;
    }
}
