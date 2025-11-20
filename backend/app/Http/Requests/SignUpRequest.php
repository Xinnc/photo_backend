<?php

namespace App\Http\Requests;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class SignUpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            "first_name" => "required|string",
            "surname" => "required|string",
            "phone" => ['required','regex:/^[0-9]{11}$/', 'digits:11', 'unique:users,phone'],
            "password" => "required|string",
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new ApiException(422,'Unprocessable entity', $validator->errors()->toArray());
    }
}
