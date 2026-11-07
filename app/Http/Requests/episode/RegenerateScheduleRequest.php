<?php

namespace App\Http\Requests\episode;

use App\Enums\SchedulePattern;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegenerateScheduleRequest extends FormRequest
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
            'schedule_pattern' => ['required', Rule::in(SchedulePattern::values())],
            'first_air_date' => 'required_unless:schedule_pattern,custom|date',
            'air_time' => 'nullable|string',
            'air_days' => 'nullable|array',
            'air_days.*' => 'integer|min:0|max:6',
            'dates' => 'nullable|array',
            'dates.*' => 'date',
        ];
    }
}
