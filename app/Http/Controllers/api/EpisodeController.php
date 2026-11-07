<?php

namespace App\Http\Controllers\api;

use App\Enums\EpisodeStatus;
use App\Enums\SchedulePattern;
use App\Http\Controllers\Controller;
use App\Http\Requests\episode\BulkStatusRequest;
use App\Http\Requests\episode\GenerateEpisodesRequest;
use App\Http\Requests\episode\RegenerateScheduleRequest;
use App\Http\Requests\episode\RescheduleEpisodeRequest;
use App\Http\Requests\episode\ShiftEpisodesRequest;
use App\Http\Requests\episode\StoreEpisodeRequest;
use App\Http\Requests\episode\UpdateEpisodeRequest;
use App\Models\Episode;
use App\Models\Movie;
use App\Services\EpisodeScheduleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EpisodeController extends Controller
{
    public function __construct(
        private EpisodeScheduleService $scheduleService
    ) {}

    public function index($id)
    {
        $movie = Movie::query()->findOrFail($id);

        return response()->json(
            $movie->episodes()->orderBy('episode_number')->get()
        );
    }

    public function store(StoreEpisodeRequest $request, $id)
    {
        $movie = Movie::query()->findOrFail($id);
        $validated = $request->validated();

        $episode = $movie->episodes()->create($validated);
        return response()->json($episode, 201);
    }

    public function update(UpdateEpisodeRequest $request, $id, $episodeId)
    {
        $movie = Movie::query()->findOrFail($id);
        $episode = Episode::query()
            ->where('movie_id', $movie->id)
            ->findOrFail($episodeId);

        $validated = $request->validated();

        $episode->update($validated);
        return response()->json($episode);
    }

    public function destroy($id, $episodeId)
    {
        $movie = Movie::query()->findOrFail($id);
        $episode = Episode::query()
            ->where('movie_id', $movie->id)
            ->findOrFail($episodeId);

        $episode->delete();
        return response()->noContent();
    }

    public function generate(GenerateEpisodesRequest $request, $id)
    {
        $movie = Movie::query()->findOrFail($id);

        $validated = $request->validated();

        try {
            $count = $this->scheduleService->generateEpisodes(
                $movie,
                $validated['total_episodes'],
                SchedulePattern::from($validated['schedule_pattern']),
                $validated
            );

            return response()->json([
                'message' => "{$count} قسمت ساخته شد.",
                'count' => $count,
                'schedule_pattern' => $validated['schedule_pattern'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function shift(ShiftEpisodesRequest $request, $id)
    {
        $movie = Movie::query()->findOrFail($id);

        $validated = $request->validated();

        $count = $this->scheduleService->shiftSchedule(
            $movie,
            $validated['from_episode'],
            $validated['days_to_shift']
        );

        return response()->json([
            'message' => "{$count} قسمت جابجا شد.",
            'count' => $count,
        ]);
    }

    public function reschedule(RescheduleEpisodeRequest $request, $id, $episodeId)
    {
        $movie = Movie::query()->findOrFail($id);
        $episode = Episode::query()
            ->where('movie_id', $movie->id)
            ->findOrFail($episodeId);

        $validated = $request->validated();

        $this->scheduleService->rescheduleEpisode(
            $episode->id,
            Carbon::parse($validated['publish_at'])
        );

        return response()->json($episode->fresh());
    }

    public function bulkStatus(BulkStatusRequest $request, $id)
    {
        $movie = Movie::query()->findOrFail($id);

        $validated = $request->validated();

        $updated = 0;
        $episodes = Episode::whereIn('id', $validated['episode_ids'])
            ->where('movie_id', $movie->id)
            ->get();

        foreach ($episodes as $ep) {
            if ($ep->status !== $validated['status']) {
                $ep->update(['status' => $validated['status']]);
                $updated++;
            }
        }

        return response()->json([
            'message' => "{$updated} قسمت آپدیت شد.",
            'count' => $updated,
        ]);
    }

    public function regenerateSchedule(RegenerateScheduleRequest $request, $id)
    {
        $movie = Movie::query()->findOrFail($id);

        $validated = $request->validated();

        $scheduledEpisodes = $movie->episodes()
            ->where('status', EpisodeStatus::SCHEDULED)
            ->orderBy('episode_number')
            ->get();

        if ($scheduledEpisodes->isEmpty()) {
            return response()->json(['message' => 'No scheduled episodes.'], 200);
        }

        try {
            $dates = $this->scheduleService->calculateDatesForExisting(
                $scheduledEpisodes->count(),
                SchedulePattern::from($validated['schedule_pattern']),
                $validated
            );

            DB::transaction(function () use ($scheduledEpisodes, $dates) {
                foreach ($scheduledEpisodes as $i => $episode) {
                    $episode->update(['publish_at' => $dates[$i]]);
                }
            });

            return response()->json([
                'message' => "{$scheduledEpisodes->count()} قسمت بازسازی شد.",
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}