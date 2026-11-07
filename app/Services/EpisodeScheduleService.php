<?php

namespace App\Services;

use App\Enums\EpisodeStatus;
use App\Enums\SchedulePattern;
use App\Models\Episode;
use App\Models\Movie;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EpisodeScheduleService
{
    public function generateEpisodes(
        Movie $movie,
        int $totalEpisodes,
        SchedulePattern $pattern,
        array $options = []
    ): int {
        if (!$movie->isSeries()) {
            throw new InvalidArgumentException('Only series can have multiple episodes.');
        }

        if ($movie->episodes()->exists()) {
            throw new \RuntimeException('Episodes already exist for this series.');
        }

        $dates = $this->calculateDates($totalEpisodes, $pattern, $options);

        if (count($dates) < $totalEpisodes) {
            throw new InvalidArgumentException(
                'Not enough dates generated. Check schedule parameters.'
            );
        }

        $episodes = [];
        $defaultRuntime = $options['runtime'] ?? $movie->runtime;

        for ($i = 0; $i < $totalEpisodes; $i++) {
            $episodes[] = [
                'movie_id' => $movie->id,
                'title' => "قسمت " . ($i + 1),
                'episode_number' => $i + 1,
                'status' => EpisodeStatus::SCHEDULED->value,
                'publish_at' => $dates[$i],
                'runtime' => $defaultRuntime,
                'is_free' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('episodes')->insert($episodes);

        // ⬇️⬇️⬇️ total_episodes هم آپدیت بشه ⬇️⬇️⬇️
        $movie->update([
            'schedule_pattern' => $pattern->value,
            'air_days' => $options['air_days'] ?? null,
            'air_time' => $options['air_time'] ?? null,
            'first_air_date' => Carbon::parse($dates[0])->toDateString(),
            'total_episodes' => $totalEpisodes,
        ]);

        return count($episodes);
    }

    public function createMovieEpisode(
        Movie $movie,
        Carbon $publishAt,
        ?int $runtime = null,
        EpisodeStatus $status = EpisodeStatus::SCHEDULED
    ): Episode {
        if (!$movie->isMovie()) {
            throw new InvalidArgumentException('This method is only for movies.');
        }

        if ($movie->episodes()->exists()) {
            throw new \RuntimeException('Movie already has an episode.');
        }

        return $movie->episodes()->create([
            'episode_number' => 1,
            'title' => 'فیلم کامل',
            'runtime' => $runtime ?? $movie->runtime,
            'publish_at' => $publishAt,
            'status' => $status,
        ]);
    }

    private function calculateDates(int $count, SchedulePattern $pattern, array $options): array
    {
        return match ($pattern) {
            SchedulePattern::WEEKLY => $this->calcWeekly($count, $options),
            SchedulePattern::MULTI_WEEKLY => $this->calcMultiWeekly($count, $options),
            SchedulePattern::DAILY => $this->calcDaily($count, $options),
            SchedulePattern::BINGE => $this->calcBinge($count, $options),
            SchedulePattern::CUSTOM => $this->calcCustom($count, $options),
        };
    }

    private function calcWeekly(int $count, array $options): array
    {
        $startDate = Carbon::parse($options['first_air_date'] ?? now()->addWeek())
            ->startOfDay();
        $airTime = $options['air_time'] ?? '20:00:00';
        $airDays = $options['air_days'] ?? [$startDate->dayOfWeek];

        return $this->generateByWeekdays($startDate, $airTime, $airDays, $count);
    }

    private function calcMultiWeekly(int $count, array $options): array
    {
        $startDate = Carbon::parse($options['first_air_date'] ?? now()->addWeek())
            ->startOfDay();
        $airTime = $options['air_time'] ?? '20:00:00';
        $airDays = $options['air_days'] ?? [];

        if (empty($airDays)) {
            throw new InvalidArgumentException('air_days required for multi_weekly.');
        }

        sort($airDays);
        return $this->generateByWeekdays($startDate, $airTime, $airDays, $count);
    }

    private function calcDaily(int $count, array $options): array
    {
        $startDate = Carbon::parse($options['first_air_date'] ?? now()->addDay())
            ->startOfDay();
        $airTime = $options['air_time'] ?? '20:00:00';

        $dates = [];
        for ($i = 0; $i < $count; $i++) {
            $dates[] = $startDate->copy()->addDays($i)
                ->setTimeFromTimeString($airTime);
        }
        return $dates;
    }

    private function calcBinge(int $count, array $options): array
    {
        $startDate = Carbon::parse($options['first_air_date'] ?? now()->addDay())
            ->startOfDay();
        $airTime = $options['air_time'] ?? '00:00:00';
        $baseDate = $startDate->copy()->setTimeFromTimeString($airTime);

        return array_fill(0, $count, $baseDate->copy());
    }

    private function calcCustom(int $count, array $options): array
    {
        $dates = $options['dates'] ?? [];
        if (count($dates) !== $count) {
            throw new InvalidArgumentException(
                "Expected {$count} dates, got " . count($dates)
            );
        }
        return array_map(fn($d) => Carbon::parse($d), $dates);
    }

    private function generateByWeekdays(
        Carbon $startDate,
        string $airTime,
        array $airDays,
        int $count
    ): array {
        // airDays = [0, 1, 2, 3, 4, 5, 6] (0=یکشنبه, 6=شنبه — مثل Carbon::dayOfWeek)
        $airDays = array_values(array_unique(array_map('intval', $airDays)));
        sort($airDays);

        if (empty($airDays)) {
            throw new InvalidArgumentException('air_days required for weekly schedules.');
        }

        $dates = [];
        $current = $startDate->copy()->setTimeFromTimeString($airTime);

        // اگه startDate جزو airDays نیست، برو به اولین روز بعدی
        $safety = 0;
        while (!in_array($current->dayOfWeek, $airDays)) {
            $current->addDay();
            $safety++;
            if ($safety > 7) {
                throw new \RuntimeException('Could not find a matching weekday within 7 days.');
            }
        }

        // از اولین روز شروع کن، هر روز چک کن
        while (count($dates) < $count) {
            if (in_array($current->dayOfWeek, $airDays)) {
                $dates[] = $current->copy();
            }
            $current->addDay();
        }

        return $dates;
    }

    public function shiftSchedule(
        Movie $movie,
        int $fromEpisodeNumber,
        int $daysToShift
    ): int {
        return DB::transaction(function () use ($movie, $fromEpisodeNumber, $daysToShift) {
            $episodes = $movie->episodes()
                ->where('episode_number', '>=', $fromEpisodeNumber)
                ->where('status', EpisodeStatus::SCHEDULED)
                ->whereNotNull('publish_at')
                ->get();

            $count = 0;
            foreach ($episodes as $episode) {
                $episode->update([
                    'publish_at' => $episode->publish_at->copy()->addDays($daysToShift),
                ]);
                $count++;
            }

            return $count;
        });
    }

    public function rescheduleEpisode(int $episodeId, Carbon $newDate): bool
    {
        $episode = Episode::findOrFail($episodeId);
        return $episode->update(['publish_at' => $newDate]);
    }

    public function calculateDatesForExisting(
        int $count,
        SchedulePattern $pattern,
        array $options
    ): array {
        return $this->calculateDates($count, $pattern, $options);
    }
}
