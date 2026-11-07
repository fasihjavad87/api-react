<?php

namespace App\Http\Requests\movie;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SyncMediaRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'media'             => ['required', 'array'],
            'media.*.id'        => ['required', 'integer', 'exists:media,id'],
            'media.*.index'     => ['required', 'integer', 'in:0,1'],
        ];
    }

    public function messages(): array
    {
        return [
            'media.required'           => 'حداقل یک رسانه انتخاب کنید.',
            'media.*.id.exists'        => 'یکی از رسانه‌ها معتبر نیست.',
            'media.*.index.in'         => 'مقدار index فقط 0 یا 1 می‌تواند باشد.',
        ];
    }
}
