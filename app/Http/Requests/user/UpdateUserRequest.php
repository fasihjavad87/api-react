<?php

namespace App\Http\Requests\user;

use App\Enums\UserStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $id = $this->route('id');
        return [
            'name' => 'sometimes|string|max:255',
            'username' => ['sometimes', 'string', 'max:255', Rule::unique('users')->ignore($id)],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users')->ignore($id)],
            'roles' => 'sometimes|array',
            'roles.*' => 'integer|exists:roles,id',
            'avatar' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_admin' => 'sometimes|boolean',
            'status' => ['sometimes', 'string', Rule::in(array_column(UserStatus::cases(), 'value'))],
            'password' => 'sometimes|string|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'نام باید متن باشد',
            'name.max' => 'نام باید کمتر از 255 کاراکتر باشد',

            'username.string' => 'نام کاربری باید متن باشد',
            'username.max' => 'نام کاربری باید کمتر از 255 کاراکتر باشد',
            'username.unique' => 'نام کاربری باید منحصر به فرد باشد',

            'email.email' => 'ایمیل باید از ساختار صحیح ایمیل پیروی کند',
            'email.max' => 'ایمیل باید کمتر از 255 کاراکتر باشد',
            'email.unique' => 'ایمیل باید منحصر به فرد باشد',

            'avatar.image' => 'آواتار باید عکس باشد',
            'avatar.mimes' => 'آواتار فقط با فرمت های jpg,jpeg,png,webp مجاز است',

            'is_admin.boolean' => 'مقدار ادمین باید true یا false باشد',

            'status.string' => 'وضعیت باید متن باشد',
            'status.in' => 'وضعیت انتخاب شده معتبر نیست',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'نام',
            'username' => 'نام کاربری',
            'email' => 'ایمیل',
            'avatar' => 'آواتار',
            'status' => 'وضعیت',
        ];
    }
}