<?php

namespace App\Http\Requests\episode;

use App\Enums\EpisodeStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkStatusRequest extends FormRequest
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
            'episode_ids' => 'required|array',
            'episode_ids.*' => 'integer|exists:episodes,id',
            'status' => ['required', Rule::in(EpisodeStatus::values())],
        ];
    }
}
