<?php

namespace App\Http\Requests;

use App\Domain\Enums\MessageCode;
use App\Exceptions\FormValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sender' => ['sometimes', 'string', 'max:32', 'exists:users,code'],
            'recipient' => ['sometimes', 'string', 'max:32', 'exists:users,code'],
            'messageCode' => ['sometimes', Rule::enum(MessageCode::class)],
            'messageId' =>  ['sometimes', 'string', 'max:64', 'unique:messages,message_id'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new FormValidationException($validator);
    }
}
