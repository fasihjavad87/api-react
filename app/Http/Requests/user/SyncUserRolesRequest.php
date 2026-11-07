<?php

namespace App\Http\Requests\user;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SyncUserRolesRequest extends FormRequest
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
            // 'roles' => ['required', 'array'],
            'roles' => ['array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'roles.required' => 'حداقل یک نقش باید انتخاب شود.',
            'roles.array' => 'فرمت نقش‌ها نامعتبر است.',
            'roles.*.exists' => 'یکی از نقش‌های انتخاب شده وجود ندارد.',
        ];
    }
}