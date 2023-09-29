<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateGenerateLinkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function messages()
    {
        return [
            'requestId.required' => 'El campo requestId es obligatorio.',
            'gateway.required' => 'El campo gateway es obligatorio.',
            'type.required' => 'El campo type es obligatorio.',
            'contract_entity_id.required' => 'El campo contract_entity_id es obligatorio.',
            'contract_so.required' => 'El campo contract_so es obligatorio.',
            'status.required' => 'El campo status es obligatorio.',
            'quotes.required' => 'El campo quotes es obligatorio.',
            'country.required' => 'El campo country es obligatorio.',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'requestId' => 'required',
            'gateway' => 'required',
            'type' => 'required',
            'contract_entity_id' => 'required',
            'contract_so' => 'required',
            'status' => 'required',
            'quotes' => 'required',
            'country' => 'required',
        ];
    }

}