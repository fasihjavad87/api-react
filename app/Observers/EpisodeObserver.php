<?php

namespace App\Observers;

use App\Enums\EpisodeStatus;
use App\Enums\PlaybackStatus;
use App\Models\Episode;
use App\Models\Movie;

class EpisodeObserver
{
    public function saved(Episode $episode): void
    {
        $this->refreshMovieStats($episode->movie_id);
    }

    public function deleted(Episode $episode): void
    {
        $this->refreshMovieStats($episode->movie_id);
    }

    private function refreshMovieStats(int $movieId): void
    {
        $movie = Movie::find($movieId);
        if (!$movie) {
            return;
        }

        $now = now();
        $totalCount = $movie->episodes()->count();

        // ==================== محاسبه وضعیت پخش ====================
        if ($totalCount === 0) {
            $status = PlaybackStatus::UPCOMING;
        } else {
            $publishedCount = $movie->episodes()
                ->where('status', EpisodeStatus::PUBLISHED)
                ->where('publish_at', '<=', $now)
                ->count();

            $status = match(true) {
                $publishedCount === 0 => PlaybackStatus::UPCOMING,
                $publishedCount === $totalCount => PlaybackStatus::ENDED,
                default => PlaybackStatus::AIRING,
            };
        }

        // ==================== جمع کردن تغییرات ====================
        $updates = [];

        if ($movie->playback_status !== $status) {
            $updates['playback_status'] = $status;
        }

        // ⬇️⬇️⬇️ مهم: total_episodes فقط اگه صفر بود آپدیت بشه ⬇️⬇️⬇️
        // (یعنی ادمین هنوز تعدادی وارد نکرده)
        if ($movie->total_episodes === 0 && $totalCount > 0) {
            $updates['total_episodes'] = $totalCount;
        }

        if (!empty($updates)) {
            $movie->updateQuietly($updates);
        }
    }
}