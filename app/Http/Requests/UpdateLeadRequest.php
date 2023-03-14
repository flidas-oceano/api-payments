<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;


class UpdateLeadRequest extends FormRequest
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
                'message' => 'Error de validacion en los campos del lead.',
                'errors' => $errors,
                'progress' => 2,
            ], 422);
        }

        return redirect()->back()
            ->withInput($this->input())
            ->withErrors($errors);
    }
    public function messages()
    {
        return [
            "name.required"=> "El campo nombre es obligatorio",
            "username.required"=> "El campo apellido es obligatorio",
            "email.required"=> "El campo email es obligatorio",
            "profession.required"=> "El campo profesion es obligatorio",
            "speciality.required"=> "El campo especialidad postal es obligatorio",
            "telephone.required"=> "El campo telefono es obligatorio",
            "method_contact.required"=> "El campo metodo de contacto es obligatorio",
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
            "name"=> "required",
            "username"=> "required",
            "email"=> "required|email|min:8|",
            "telephone"=> "required",
            "profession"=> "required",
            "speciality"=> "required",
            "method_contact"=> "required",
        ];
    }
 
}
