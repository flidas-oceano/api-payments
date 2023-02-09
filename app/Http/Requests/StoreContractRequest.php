<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // "contact.contact_id"=> "required",
            // "contact.entity_id_crm"=> "required",
            "dni"=> "required",
            "sex"=> "required",
            "date_of_birth"=> "required",
            // "contact.addresses_id_fk"=> "required",
            // "contact.registration_number"=> "required",
            // "contact.area_of_work"=> "required",
            // "contact.training_interest"=> "required",
            // "address.address_id"=> "required",
            "country"=> "required",
            "province_state"=> "required",
            "postal_code"=> "required",
            "street"=> "required",
            "locality"=> "required",
            
        ];
    }
}
