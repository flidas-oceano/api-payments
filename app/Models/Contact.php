<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\{Lead,PurchasingProcess};
class Contact extends Model
{
    use HasFactory;
    protected $table = 'contacts';
    protected $fillable = [
        'entity_id_crm',
        'dni',
        'sex',
        'date_of_birth',
        'addresses_id_fk',
        'registration_number',
        'area_of_work',
        'training_interest',
    ];
    public $timestamps = false;
    public function lead(){
        // $lead = Lead::where('contact_id_fk', $this->id)->first();
        // $lead = $this->belongsTo(Lead::class,'id','leads');
        $lead = $this->hasOne(Lead::class,'contact_id_fk');
         return $lead;
        // return $lead;
    }
}
