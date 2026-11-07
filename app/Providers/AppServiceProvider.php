<?php

namespace App\Providers;

use App\Models\Episode;
use App\Models\Movie;
use App\Models\Sanctum\PersonalAccessToken;
use App\Observers\EpisodeObserver;
use App\Observers\MovieObserver;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        Episode::observe(EpisodeObserver::class);
        Movie::observe(MovieObserver::class);
    }
}
