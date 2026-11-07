<?php

namespace App\Guards;

use Laravel\Sanctum\Guard as BaseSanctumGuard;
use Laravel\Sanctum\Sanctum;

class SanctumGuard extends BaseSanctumGuard
{
    /**
     * بررسی اعتبار توکن بر اساس last_used_at (و در صورت نبود، created_at)
     */
    protected function isValidAccessToken($accessToken): bool
    {
        if (! $accessToken) {
            return false;
        }

        // بررسی تطبیق Provider کاربر
        if (! $this->hasValidProvider($accessToken->tokenable)) {
            return false;
        }

        // بررسی ستون expires_at در صورت وجود
        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
            return false;
        }

        // اعتبارسنجی انقضا بر اساس آخرین استفاده (Idle Expiration)
        if ($this->expiration) {
            $lastActivity = $accessToken->last_used_at ?? $accessToken->created_at;

            if ($lastActivity->lte(now()->subMinutes($this->expiration))) {
                return false;
            }
        }

        // پشتیبانی از کال‌بک رسمی Sanctum
        if (is_callable(Sanctum::$accessTokenAuthenticationCallback)) {
            return (bool) (Sanctum::$accessTokenAuthenticationCallback)($accessToken, true);
        }

        return true;
    }

    /**
     * بروزرسانی last_used_at تنها در صورتی که حداقل ۲ دقیقه گذشته باشد
     */
    protected function updateLastUsedAt($accessToken): void
    {
        // فاصله زمانی مد نظر: ۲ دقیقه
        $intervalMinutes = config('sanctum.last_used_interval', 2);

        // اگر کمتر از فاصله زمانی گذشته باشد، آپدیت نمی‌کنیم
        if (
            $accessToken->last_used_at &&
            $accessToken->last_used_at->gt(now()->subMinutes($intervalMinutes))
        ) {
            return;
        }

        // ذخیره زمان فعلی بدون ریست وضعیت Track لاراول
        if (method_exists($accessToken->getConnection(), 'setRecordModificationState')) {
            $hasModifiedRecords = $accessToken->getConnection()->hasModifiedRecords();
            
            $accessToken->forceFill(['last_used_at' => now()])->updateQuietly();
            
            $accessToken->getConnection()->setRecordModificationState($hasModifiedRecords);
        } else {
            $accessToken->forceFill(['last_used_at' => now()])->updateQuietly();
        }
    }
}
