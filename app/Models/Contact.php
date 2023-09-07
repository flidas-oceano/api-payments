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
        'id',
        'entity_id_crm',
        'sex',
        'date_of_birth',
        'registration_number',
        'area_of_work',
        'training_interest',
        'type_of_address',
        'postal_code',
        'street',
        'locality',
        'province_state',
        'lead_id',
        'country',
        'dni',
        'rut',
        'rfc',
        'mui',
    ];

    private static $formAttributes = [
        'sex',
        'date_of_birth',
        'registration_number',
        'area_of_work',
        'training_interest',
        'type_of_address',
        'postal_code',
        'street',
        'locality',
        'province_state',
        'entity_id_crm',
        'country',
        'dni',
        'rut',
        'rfc',
        'dni',
    ];

    public function lead()
    {
        $lead = $this->hasOne(Lead::class, 'contact_id', 'id');
        return $lead;
    }
    public static function getFormAttributes()
    {
        return self::$formAttributes;
    }
}
