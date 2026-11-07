<?php

namespace App\Http\Requests\movie;

use App\Enums\ContentType;
use App\Enums\MovieStatus;
use App\Enums\PlaybackStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexMovieRequest extends FormRequest
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
            'content_type'=>['nullable',Rule::in(ContentType::values())],
            'status'=>['nullable', Rule::in(MovieStatus::values())],
            'playback_status'=>['nullable', Rule::in(PlaybackStatus::values())],
            'search'=>'nullable|string|max:255',
            'searchChar'=>'nullable|string|max:255',
            'count'=>'nullable|integer|min:1|max:100',
        ];
    }
}
