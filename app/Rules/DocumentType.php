<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class DocumentType implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

     /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // Valida que el tipo de documento sea CI o RUC
        return in_array($value, ['CI', 'RUC', 'CC']);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'El tipo de documento debe ser CI, CC o RUC.';
    }
}
