<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\{Lead};

class Contact extends Model
{
    use HasFactory;
    protected $table = 'contacts';
    protected $fillable = [
        'entity_id_crm',
        'dni', 'sex', 'date_of_birth', 'province_state', 'postal_code', 'street', 'locality', 'registration_number', 'area_of_work', 'training_interest','lead_id'
    ];

    private static $formAttributes = ['dni', 'sex', 'date_of_birth', 'province_state', 'postal_code', 'street', 'locality', 'registration_number', 'area_of_work', 'training_interest'];

    public function lead()
    {
        $lead = $this->hasOne(Lead::class, 'contact_id','id');
        return $lead;
    }

    public static function getFormAttributes()
    {
        return self::$formAttributes;
    }
}
