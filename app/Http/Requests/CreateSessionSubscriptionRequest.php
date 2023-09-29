<?php

namespace App\Http\Requests;

use Dotenv\Validator as DotenvValidator;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;


class CreateSessionSubscriptionRequest extends FormRequest
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
            'so.required' => 'El campo SO es obligatorio.',
            'payer.name.required' => 'El campo Nombre del pagador es obligatorio.',
            'payer.surname.required' => 'El campo Apellido del pagador es obligatorio.',
            'payer.email.required' => 'El campo Correo electrónico del pagador es obligatorio.',
            'payer.email.email' => 'El campo Correo electrónico del pagador debe ser una dirección de correo válida.',
            'payer.documentType.required' => 'El campo Tipo de documento es obligatorio.',
            'payer.documentType.in' => 'El campo Tipo de Documento debe ser CI, RUC o PPN.',
            'payer.document.required' => 'El campo Número de Documento es obligatorio.',
            'payer.mobile.required' => 'El campo Teléfono móvil del pagador es obligatorio.',
            'payer.address.street.required' => 'El campo Calle de la dirección del pagador es obligatorio.',
            'payment.total.required' => 'El campo Total del pago es obligatorio.',
            'payment.total.numeric' => 'El campo Total del pago debe ser un número válido.',
            'payment.quotes.required' => 'El campo Cuotas es obligatorio.',
            'payment.quotes.integer' => 'El campo Cuotas debe ser un número entero.',
            'payment.remaining_installments.required' => 'El campo Cuotas restantes es obligatorio.',
            'payment.remaining_installments.numeric' => 'El campo Cuotas restantes debe ser un número válido.',
            'payment.type.required' => 'El campo Tipo de pago es obligatorio.',
            'payment.type.in' => 'El campo Tipo de pago debe ser Suscripción o Parcialidad.',
            'payment.first_installment.required_if' => 'El campo Primera Cuota es obligatorio',
            'payment.first_installment.decimal' => 'El campo Primera Cuota es obligatorio.',
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
            'so' => 'required|string',
            'payer.name' => 'required|string',
            'payer.surname' => 'required|string',
            'payer.email' => 'required|email',
            // 'payer.documentType' => ['required', 'string', new \App\Rules\DocumentType],
            'payer.documentType' => 'required|string|in:CI,RUC,PPN',
            'payer.document' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    // Obtén el valor del campo 'payer.documentType'
                    $documentType = $this->input('payer.documentType');

                    // Valida el número de documento según el tipo
                    if ($documentType === 'PPN' && !preg_match('/^[a-zA-Z0-9_]{4,16}$/', $value)) {// 12345678901
                        $fail("El campo $attribute no es un PPN (Pasaporte) válida.");
                    } elseif ($documentType === 'RUC' && !preg_match('/^\d{13}$/', $value)) {// 1234567890123
                        $fail("El campo $attribute no es un RUC (Registro Único de Contribuyentes) válido.");
                    } elseif ($documentType === 'CI' && !preg_match('/^\d{10}$/', $value)) {// 123456789
                        $fail("El campo $attribute no es una CI (Cédula de identidad) válida.");
                    }
                },
            ],
            'payer.mobile' => 'required|string',
            'payer.address.street' => 'required|string',
            'payment.total' => 'required|numeric',
            'payment.quotes' => 'required|integer',
            'payment.first_installment' => 'required_if:payment.type,Parcialidad|numeric',
            'payment.remaining_installments' => 'required|numeric',
            'payment.type' => 'required|string|in:Suscripción,Parcialidad',
        ];
    }
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 400));
    }
}
