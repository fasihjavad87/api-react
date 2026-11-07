<?php

namespace App\Http\Controllers\api;

use App\Enums\ContentType;
use App\Enums\EpisodeStatus;
use App\Enums\MovieStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\movie\IndexMovieRequest;
use App\Http\Requests\movie\StoreMovieRequest;
use App\Http\Requests\movie\SyncMediaRequest;
use App\Http\Requests\movie\UpdateMovieRequest;
use App\Models\Movie;
use App\Services\EpisodeScheduleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MovieController extends Controller
{
    public function __construct(
        private EpisodeScheduleService $scheduleService
    ) {}

    public function index(IndexMovieRequest $request)
    {
        $request->validated();

        $query = Movie::query()->with('details');

        if ($request->filled('content_type')) {
            $query->where('content_type', $request->content_type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('playback_status')) {
            $query->where('playback_status', $request->playback_status);
        }

        $searchTerm = $request->input('searchChar') ?: $request->input('search');
        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('e_name', 'like', "%{$searchTerm}%");
            });
        }

        $perPage = $request->input('count', 20);
        $perPage = min(max((int) $perPage, 1), 100);

        return response()->json(
            $query->orderByDesc('created_at')->paginate($perPage)
        );
    }

    public function store(StoreMovieRequest $request)
    {
        $validated = $request->validated();

        $detailsData = $validated['details'] ?? [];
        $publishAt = $validated['publish_at'] ?? null;
        unset($validated['details'], $validated['publish_at']);

        $movie = DB::transaction(function () use ($validated, $detailsData, $publishAt) {
            $movie = Movie::create($validated);

            $movie->details()->create($detailsData);

            if ($movie->isMovie() && $publishAt) {
                $status = $movie->status === MovieStatus::PUBLISH
                    ? EpisodeStatus::PUBLISHED
                    : EpisodeStatus::SCHEDULED;

                $this->scheduleService->createMovieEpisode(
                    $movie,
                    Carbon::parse($publishAt),
                    $movie->runtime,
                    $status
                );
            }

            return $movie;
        });

        return response()->json($movie->load(['details', 'episodes']), 201);
    }

    public function show($id)
    {
        $movie = Movie::query()->findOrFail($id);
        $movie->load(['details', 'episodes' => fn($q) => $q->orderBy('episode_number')]);

        // ⬇️⬇️⬇️ countdown ⬇️⬇️⬇️
        $nextEpisode = $movie->nextEpisode();
        $countdown = null;

        if ($nextEpisode) {
            $effective = $movie->effectivePublishAt();
            if ($effective && $effective->isFuture()) {
                $countdown = [
                    'type' => $movie->isMovie() ? 'movie' : 'episode',
                    'episode_number' => $nextEpisode->episode_number,
                    'airdate' => $effective->toIso8601String(),   // ⬅️ ISO (فرانت با jMoment تبدیل می‌کنه)
                    'schedule_text_fa' => $movie->details?->air_schedule_display['text_fa'] ?? null,
                    'schedule_text_en' => $movie->details?->air_schedule_display['text_en'] ?? null,
                    'seconds_till' => $movie->secondsTillPublish(),
                ];
            }
        }

        // ⬇️⬇️⬇️ همه فیلدها + countdown ⬇️⬇️⬇️
        return response()->json([
            'id' => $movie->id,
            'title' => $movie->title,
            'e_name' => $movie->e_name,
            'slug' => $movie->slug,
            'description' => $movie->description,
            'content_type' => $movie->content_type,
            'status' => $movie->status,
            'playback_status' => $movie->playback_status,
            'is_free' => $movie->is_free,
            'total_episodes' => $movie->total_episodes,
            'runtime' => $movie->runtime,
            'schedule_pattern' => $movie->schedule_pattern,
            'air_days' => $movie->air_days,
            'air_time' => $movie->air_time,
            'first_air_date' => $movie->first_air_date,
            'created_at' => $movie->created_at,
            'updated_at' => $movie->updated_at,
            'details' => $movie->details,
            'episodes' => $movie->episodes,
            'media' => $movie->media,
            'primary_media' => $movie->primary_media,
            'countdown' => $countdown,
        ]);


        // return response()->json($movie);
    }


    public function showUser(string $slug)
    {
        $movie = Movie::published()
            ->where('slug', $slug)
            ->with(['details', 'episodes' => fn($q) => $q->orderBy('episode_number')])
            ->firstOrFail();

        $publishedEpisodes = $movie->episodes()
            ->where('status', EpisodeStatus::PUBLISHED)
            ->where('publish_at', '<=', now())
            ->orderBy('episode_number')
            ->get();

        $effective = $movie->effectivePublishAt();
        $countdown = null;

        if ($effective && $effective->isFuture()) {
            $nextEpisodeNumber = $movie->episodes()
                ->where('publish_at', $effective)
                ->first()?->episode_number;

            $countdown = [
                'type' => $movie->isMovie() ? 'movie' : 'episode',
                'episode_number' => $nextEpisodeNumber,
                'airdate' => $effective->toIso8601String(),
                'airdate_fa' => $effective->translatedFormat('l j F · H:i'),
                'seconds_till' => $movie->secondsTillPublish(),
            ];
        }

        return response()->json([
            'movie' => $movie->only([
                'id',
                'title',
                'e_name',
                'slug',
                'description',
                'content_type',
                'playback_status',
                'runtime',
                'is_free',
                'total_episodes',
            ]),
            'details' => $movie->details,
            'episodes' => $publishedEpisodes,
            'media' => $movie->media,
            'primary_media' => $movie->primary_media,
            'countdown' => $countdown,
        ]);
    }



    public function update(UpdateMovieRequest $request, $id)
    {
        $movie = Movie::query()->findOrFail($id);

        $validated = $request->validated();

        $detailsData = $validated['details'] ?? null;
        $publishAt = $validated['publish_at'] ?? null;
        unset($validated['details'], $validated['publish_at']);

        DB::transaction(function () use ($movie, $validated, $detailsData, $publishAt) {
            $movie->update($validated);

            if ($detailsData !== null) {
                if ($movie->details) {
                    $movie->details->update($detailsData);
                } else {
                    $movie->details()->create($detailsData);
                }
            }

            if ($movie->isMovie() && $publishAt) {
                $ep = $movie->episodes()->where('episode_number', 1)->first();
                if ($ep) {
                    $ep->update(['publish_at' => Carbon::parse($publishAt)]);
                }
            }
        });

        return response()->json($movie->fresh(['details', 'episodes']));
    }

    public function destroy($id)
    {
        $movie = Movie::query()->findOrFail($id);
        $movie->delete();
        return response()->noContent();
    }


    public function syncMedia(SyncMediaRequest $request, $id)
    {
        $movie = Movie::findOrFail($id);

        $items = collect($request->validated('media'))
            ->map(fn($m) => [
                'id'    => (int) $m['id'],
                'index' => (int) $m['index'],
            ])
            ->values()
            ->all();

        $movie->syncMedia($items);

        return response()->json([
            'message' => 'رسانه‌ها با موفقیت ذخیره شدند.',
            'media'   => $movie->fresh()->media,
        ]);
    }

    /**
     * خروجی رسانه‌های یک فیلم/سریال
     */
    public function getMedia($id)
    {
        $movie = Movie::findOrFail($id);

        return response()->json([
            'media'         => $movie->media,
            'primary_media' => $movie->primary_media,
        ]);
    }
}
