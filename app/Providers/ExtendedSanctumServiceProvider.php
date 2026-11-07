<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Guards\SanctumGuard;
use Illuminate\Auth\RequestGuard;
use Laravel\Sanctum\Sanctum;
use Laravel\Sanctum\SanctumServiceProvider as BaseSanctumServiceProvider;

class ExtendedSanctumServiceProvider extends BaseSanctumServiceProvider
{


   protected function createGuard($auth, $config)
    {
        return new RequestGuard(
            new SanctumGuard(
                $auth,
                config('sanctum.expiration'),
                $config['provider'] ?? null,
                true // ردیابی last_used_at
            ),
            request(),
            $auth->createUserProvider($config['provider'] ?? null)
        );
    }
    /**
     * Register services.
     */
     public function register(): void
    {
        parent::register();
    }

    /**
     * اجرای متد boot والد (که ثبت گارد sanctum در آن اتفاق می‌افتد)
     */
    public function boot(): void
    {
        parent::boot();
    }
}
