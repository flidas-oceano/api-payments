<?php

namespace App\Validations\Contifico;

use Illuminate\Support\Facades\Validator;

class ContificoInvoiceValidator
{

    /**
     * @throws \Exception
     */
    public static function create($data)
    {
        $rules = [
            'identification' => 'required',
            'email' => 'required',
        ];
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            $errors = $validator->errors();
             throw new \Exception(json_encode($errors->all()));
        }
    }
}
