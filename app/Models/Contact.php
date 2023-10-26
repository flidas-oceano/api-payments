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

    public static function mappingDataContact($request, $gateway)
    {
        if ($gateway == 'Placetopay') {
            $paymentData = PlaceToPayTransaction::getPaymentDataByRequestId($request['requestId']);

            return [
                'Identificacion' => $paymentData->document,
                'Tel_fono_de_facturaci_n' => $paymentData->mobile,
                'Raz_n_social' => PlaceToPayTransaction::getFullNameFromPaymentData($paymentData),
            ];
        }

        $identification = self::getIdentification($request->dni, $request->country);

        return [
            'Identificacion' => ($request->dni ?? $identification),
            'Tel_fono_de_facturaci_n' => $request->phone,
            'Raz_n_social' => $request->fullname,
        ];
    }
    private function getIdentification($identification, $country)
    {
        if ($country == "Chile" && strpos(strval($identification), '-') == false) {
            return substr(strval($identification), 0, -1) . '-' . substr(strval($identification), -1);
        }
        return strval($identification);
    }

}
