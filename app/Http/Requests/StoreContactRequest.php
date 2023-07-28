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
            "sex.required" => "El campo sexo es obligatorio",
            "date_of_birth.required" => "El campo fecha de nacimiento es obligatorio",
            "province_state.required" => "El campo provincia/estado es obligatorio",
            "postal_code.required" => "El campo codigo postal es obligatorio",
            "street.required" => "El campo direccion es obligatorio",
            "locality.required" => "El campo localidad es obligatorio",
            "idPurchaseProgress.required" => "El id del progreso es obligatorio",
            "step_number.required" => "El numero del paso es obligatorio",

            "dni.required_if" => "El campo dni es obligatorio",
            "rut.required_if" => "El campo rut es obligatorio",

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
            "sex" => "required",
            "date_of_birth" => "required",
            "province_state" => "required",
            'postal_code' => 'required_unless:country,Chile',
            "street" => "required",
            "country" => "required",
            "locality" => "required",
            "idPurchaseProgress" => "required",
            "step_number" => "required",

            //identificacion
            'rut' => 'required_if:country,Chile',
            'rfc' => 'required_if:country,México',
            'mui' => 'required_if:country,Ecuador',
            'dni' => 'required_if:country,Argentina',


        ];
    }
}