<?php

namespace App\Http\Requests\episode;

use App\Enums\SchedulePattern;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateEpisodesRequest extends FormRequest
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
            'total_episodes' => 'required|integer|min:1|max:1000',
            'schedule_pattern' => ['required', Rule::in(SchedulePattern::values())],
            'first_air_date' => 'required_unless:schedule_pattern,custom|date',
            'air_time' => 'nullable|string',
            'air_days' => 'required_if:schedule_pattern,multi_weekly|array|min:1|max:7',
            'air_days.*' => 'integer|min:0|max:6',
            'dates' => 'required_if:schedule_pattern,custom|array',
            'dates.*' => 'date',
            'runtime' => 'nullable|integer|min:1|max:65535',
        ];
    }
}
