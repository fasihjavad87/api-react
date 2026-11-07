<?php

namespace App\Http\Requests\episode;

use App\Enums\EpisodeStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEpisodeRequest extends FormRequest
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
        $movieId = $this->route('movie')->id ?? $this->route('movie');

        return [
            'title' => 'nullable|string|max:255',
            'episode_number' => [
                'required',
                'integer',
                'min:1',
                'max:65535',
                Rule::unique('episodes')->where('movie_id', $movieId)
            ],
            'status' => ['required', Rule::in(EpisodeStatus::values())],
            'publish_at' => 'nullable|date',
            'runtime' => 'nullable|integer|min:1|max:65535',
            'is_free' => 'sometimes|boolean',
        ];
    }
}
