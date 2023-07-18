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
        return true;
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Validation\ValidationException($validator, $this->response($validator));
    }

    protected function response($validator)
    {
        $errors = $validator->errors();


        if ($this->expectsJson()) {
            return response()->json([
                'message' => 'Error de validacion en los campos del contacto.',
                'errors' => $errors,
                'progress' => 3,
            ], 422);
        }

        return redirect()->back()
            ->withInput($this->input())
            ->withErrors($errors);
    }

    public function messages()
    {
        return [
            "dni.required" => "El campo dni es obligatorio",
            "sex.required" => "El campo sexo es obligatorio",
            "date_of_birth.required" => "El campo fecha de nacimiento es obligatorio",
            "province_state.required" => "El campo provincia/estado es obligatorio",
            "postal_code.required" => "El campo codigo postal es obligatorio",
            "street.required" => "El campo direccion es obligatorio",
            "locality.required" => "El campo localidad es obligatorio",
            "idPurchaseProgress.required" => "El id del progreso es obligatorio",
            "step_number.required" => "El numero del paso es obligatorio",
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
            "dni" => "required_if:country,Argentina",
            "rut" => "required_if:country,Chile",
            "rfc" => "required_if:country,México",
            "sex" => "required",
            "date_of_birth" => "required",
            "province_state" => "required",
            'postal_code' => 'required_unless:country,Chile',
            "street" => "required",
            "locality" => "required",
            "idPurchaseProgress" => "required",
            "step_number" => "required",
            // "country"=> "Chile",
            // "name"=> "RobertoCL1",
            // "username"=> "PruebaFlores",
            // "profession"=> "1",
            // "telephone"=> "5642424234234",
            // "speciality"=> "2",
            // "method_contact"=> "1",
            // "dni"=> "998989595998",
            // "registration_number"=> "15454545",
            // "area_of_work"=> "Licenciado",
            // "training_interest"=> "test",
            // "email"=> "robertopruebaCL@oceano.com.ar",
        ];
    }
}