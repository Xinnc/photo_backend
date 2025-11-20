<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePhotoRequest extends FormRequest
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
            '_method' => ['required', 'in:patch'],
            'name' => ['nullable', 'string', 'max:255'],
            'photo' => [
                'nullable',
                'string',
                'regex:/^data:image\/(jpeg|png|jpg);base64,/',
                function ($attr, $value, $fail) {
                    $data = preg_replace('/^data:image\/\w+;base64,/', '', $value);
                    $binary = base64_decode($data, true);
                    if ($binary === false) {
                        $fail('Invalid base64 data.');
                        return;
                    }
                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->buffer($binary);
                    if (!in_array($mime, ['image/jpeg', 'image/png'])) {
                        $fail('Only JPEG and PNG images are allowed.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            '_method.required' => 'The _method field is required.',
            '_method.in' => 'The _method must be "patch".',
            'photo.regex' => 'Photo must be a base64-encoded JPEG or PNG image.',
        ];
    }
}
