<?php

namespace App\Observers;

use App\Models\Movie;
use App\Services\AirScheduleFormatter;

class MovieObserver
{
    public function saved(Movie $movie): void
    {
        if (!$movie->isSeries()) {
            return;
        }

        if ($movie->wasChanged(['air_days', 'air_time', 'schedule_pattern'])) {
            $this->updateAirScheduleDisplay($movie);
        }
    }

    private function updateAirScheduleDisplay(Movie $movie): void
    {
        $details = $movie->details;
        if (!$details) {
            return;
        }

        $airDays = $movie->air_days ?? [];
        $airTime = $movie->air_time;

        $display = AirScheduleFormatter::format($airDays, $airTime);

        $details->updateQuietly(['air_schedule_display' => $display]);
    }
}