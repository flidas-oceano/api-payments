<?php

namespace App\Validations;

use Illuminate\Support\Facades\Validator;

class ContificoValidator
{

    /**
     * @throws \Exception
     */
    public static function createUser($data)
    {
        $rules = [
            'identification' => 'required',
            'document' => 'required',
            'email' => 'required',
        ];
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            $errors = $validator->errors();
             throw new \Exception(json_encode($errors->all()));
        }
    }
}
