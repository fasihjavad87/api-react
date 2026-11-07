<?php

namespace App\Http\Requests\role;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SyncRolePermissionsRequest extends FormRequest
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
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'permissions.required' => 'حداقل یک مجوز باید انتخاب شود.',
            'rolespermissions.array' => 'فرمت مجوزها نامعتبر است.',
            'rolespermissions.*.exists' => 'یکی از مجوزهای انتخاب شده وجود ندارد.',
        ];
    }
}