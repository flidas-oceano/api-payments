<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\{PurchasingProcess,Contact};

class Lead extends Model
{
    use HasFactory;
    protected $table = 'leads';
    protected $fillable = [
        'id',
        'entity_id_crm',
        'lead_status',
        'source_lead',
        'lead_source',
        'name',
        'username',
        'email',
        'telephone',
        'method_contact',
        'contact_id_fk',
        'method_contact_id_fk',
        'profession',
        'speciality'
    ];
    public $timestamps = true;
    public $hidden = ['created_at','updated_at','source_lead','lead_status','lead_source','id'];

    public function purchasingProcesses(){
        $purchasingProcesses = $this->hasMany(PurchasingProcess::class,'lead_id_fk','id');
        return $purchasingProcesses;
    }

    public function profession(){
        $profession = Profession::where('id',$this->profession)->first()->name; 
        return $profession;
    }

    public function contact(){
        // $contact = Contact::where('id', $this->contact_id_fk)->first();
         $contact = $this->belongsTo(Contact::class,'contact_id_fk');
         return $contact;
        // return $contact;
    }
}
