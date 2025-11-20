<?php

namespace App\Http\Requests;

use App\Exceptions\ApiException;
use Illuminate\Foundation\Http\FormRequest;

class StorePhotoRequst extends FormRequest
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
    public function rules()
    {
        return [
            'photo' => [
                'required',
                'string',
                'regex:/^data:image\/(jpeg|png|jpg);base64,/',
                function ($attr, $value) {
                    $data = preg_replace('/^data:image\/\w+;base64,/', '', $value);
                    $binary = base64_decode($data, true);
                    if ($binary === false) {
                        throw new ApiException(422, "invalid base64 string");
                    }
                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->buffer($binary);
                    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/jpg'])) {
                        throw new ApiException(422, "invalid image type");
                    }
                },
            ],
        ];
    }
    public function messages()
    {
        return [
            'photo.required' => 'The photo field is required.',
            'photo.string' => 'The photo must be a string (base64).',
            'photo.regex' => 'The photo must be a base64-encoded JPEG or PNG image.',
        ];
    }
}
