<?php

namespace App\Models\Concerns;

use App\Enums\EpisodeStatus;
use App\Models\Episode;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasPublishSchedule
{
    public function publishedEpisodes(): HasMany
    {
        return $this->episodes()
            ->where('status', EpisodeStatus::PUBLISHED)
            ->where('publish_at', '<=', now());
    }

    public function effectivePublishAt(): ?Carbon
    {
        $next = $this->episodes()
            ->where('status', EpisodeStatus::SCHEDULED)
            ->where('publish_at', '>', now())
            ->orderBy('publish_at')
            ->first();

        if ($next) {
            return $next->publish_at;
        }

        return $this->episodes()
            ->where('status', EpisodeStatus::PUBLISHED)
            ->whereNotNull('publish_at')
            ->orderBy('publish_at')
            ->first()?->publish_at;
    }

    public function nextEpisode(): ?Episode
    {
        return $this->episodes()
            ->where('status', EpisodeStatus::SCHEDULED)
            ->where('publish_at', '>', now())
            ->orderBy('publish_at')
            ->first();
    }

    public function hasCountdown(): bool
    {
        $effective = $this->effectivePublishAt();

        return $effective !== null && $effective->isFuture();
    }

    public function secondsTillPublish(): ?int
    {
        $effective = $this->effectivePublishAt();

        if (!$effective || $effective->isPast()) {
            return null;
        }

        return max(0, (int) now()->diffInSeconds($effective, false));
    }
}