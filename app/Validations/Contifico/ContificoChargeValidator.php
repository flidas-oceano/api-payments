<?php

namespace App\Validations\Contifico;

use Illuminate\Support\Facades\Validator;

class ContificoChargeValidator
{

    /**
     * @throws \Exception
     */
    public static function create($data)
    {
        $rules = [
            'invoice_id' => 'required',
            'date' => 'required',
            'value_charged' => 'required',
            'charge_type' => 'required',
            'charge_method' => 'required',
        ];
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            $errors = $validator->errors();
             throw new \Exception(json_encode($errors->all()));
        }
    }
}
