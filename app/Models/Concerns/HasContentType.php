<?php

namespace App\Models\Concerns;

use App\Enums\ContentType;

trait HasContentType
{
    public function isSeries(): bool
    {
        return $this->content_type === ContentType::SERIES;
    }

    public function isMovie(): bool
    {
        return $this->content_type === ContentType::MOVIE;
    }
}