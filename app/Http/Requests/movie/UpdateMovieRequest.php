<?php

namespace App\Http\Requests\movie;

use App\Enums\ContentType;
use App\Enums\MovieStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMovieRequest extends FormRequest
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
            'title'=>'sometimes|string|max:255',
            'e_name'=>'sometimes|string|max:255',
            'slug'=>['sometimes|string|max:255', Rule::unique('movies','slug')->ignore($movieId)],
            'description'=>'nullable|string',
            'content_type'=>['sometimes', Rule::in(ContentType::values())],
            'status'=>['sometimes', Rule::in(MovieStatus::values())],
            'runtime'=>'nullable|integer|min:1|max:65535',
            'is_free'=>'sometimes|boolean',
            'total_episodes'=>'nullable|integer|min:0|max:1000',
            'publish_at'=>'nullable|date',
            'schedule_pattern'=>'nullable|string|max:100',
            'air_days'=>'nullable|array',
            'air_days.*'=>'integer|min:0|max:6',
            'air_time'=>'nullable|string',
            'first_air_date'=>'nullable|date',
            'details'=>'nullable|array',
            'details.release_year'=>'nullable|integer',
            'details.age_rating'=>'nullable|string',
            'details.rating'=>'nullable|numeric|min:1|max:10',
            'details.seasons'=>'nullable|array',
        ];
    }
}
