<?php

namespace App\Exceptions;

use Illuminate\Validation\ValidationException;

class FormValidationException extends ValidationException
{
    private const int CUSTOM_VALIDATION_ERROR_CODE = 422;

    public $status = 200;

    public function __construct($validator)
    {
        $response = response()->json([
            'result' => null,
            'code' => self::CUSTOM_VALIDATION_ERROR_CODE,
            'description' => self::summarize($validator),
            'errorMessages' => $validator->messages() ?: null,
        ]);
        parent::__construct($validator, $response);
    }
}
