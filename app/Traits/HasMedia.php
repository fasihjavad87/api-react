<?php

namespace App\Traits;

use App\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;

trait HasMedia
{
    public function media(): MorphToMany
    {
        return $this->morphToMany(Media::class, 'mediable')
            ->withPivot(['index', 'order'])
            ->withTimestamps()
            ->orderByPivot('index', 'desc')
            ->orderByPivot('order');
    }

    public function primaryMedia(): MorphToMany
    {
        return $this->morphToMany(Media::class, 'mediable')
            ->withPivot(['index', 'order'])
            ->wherePivot('index', 1);
    }

    // public function getPrimaryMediaAttribute(): ?Media
    // {
    //     return $this->primaryMedia()->first();
    // }
    public function getPrimaryMediaAttribute(): ?Media
    {
        if ($this->relationLoaded('media')) {
            return $this->media->firstWhere('pivot.index', 1)
                ?? $this->media->first();
        }

        return $this->primaryMedia()->first();
    }

    public function getPrimaryMediaUrlAttribute(): ?string
    {
        return $this->primary_media?->getUrl();
    }

    public function syncMedia(array $items): void
    {
        DB::transaction(function () use ($items) {
            $primaryCount = collect($items)->where('index', 1)->count();
            if ($primaryCount > 1) {
                throw new \InvalidArgumentException(
                    'فقط یک رسانه می‌تواند index=1 داشته باشد.'
                );
            }

            if ($primaryCount === 0 && count($items) > 0) {
                $items[0]['index'] = 1;
            }

            $pivotData = [];
            foreach ($items as $order => $item) {
                $pivotData[$item['id']] = [
                    'index' => (int)($item['index'] ?? 0),
                    'order' => $order
                ];
            }

            $this->media()->sync($pivotData);
        });
    }

    public function setPrimaryMedia(int $mediaId): void
    {
        DB::transaction(function () use ($mediaId) {
            $this->media()->updateExistingPivot(
                $this->media()->pluck('media.id')->toArray(),
                ['index' => 0]
            );

            $this->media()->updateExistingPivot($mediaId, ['index' => 1]);
        });
    }
}
