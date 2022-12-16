<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\{Lead,PurchasingProcess};
class Contact extends Model
{
    use HasFactory;
    protected $table = 'contacts';
    protected $fillable = ['entity_id_crm','username','date_of_birth','registration_number','training_interest'];

    public function lead(){
        $lead = $this->hasOneThrough(PurchasingProcess::class,Lead::class,'contact_id_fk','lead_id_fk','id');
        return $lead;
    }
}
