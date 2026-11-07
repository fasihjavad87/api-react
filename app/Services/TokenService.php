<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

class TokenService
{
    /**
     * ساخت هش SHA-256 از توکن خام
     */
    public static function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    /**
     * صدور توکن جدید و ذخیره در ردیس
     */
    public function createToken(User $user, Request $request, bool $remember = false): array
    {
        $plainToken = Str::random(64);
        $tokenHash  = self::hashToken($plainToken);

        $agent = new Agent();
        $agent->setUserAgent($request->userAgent());

        $ttlSeconds = $remember ? (90 * 24 * 60 * 60) : (5 * 24 * 60 * 60); // ۳ ماه یا ۵ روز
        $expiresAt  = Carbon::now()->addSeconds($ttlSeconds);

        $sessionData = [
            'user_id'     => $user->id,
            'device_id'   => $request->header('X-Device-Id') ?? Str::random(32),
            'ip_address'  => $request->ip(),
            'platform'    => $agent->platform() ?: 'نامشخص',
            'browser'     => $agent->browser() ?: 'نامشخص',
            'device_type' => $agent->isMobile() ? 'Mobile' : ($agent->isTablet() ? 'Tablet' : 'Desktop'),
            'last_active' => now()->timestamp,
            'expires_at'  => $expiresAt->toDateTimeString(),
        ];

        // ۱. ذخیره اطلاعات توکن با کلید هش‌شده در Redis
        Redis::setex("auth_token:{$tokenHash}", $ttlSeconds, json_encode($sessionData));

        // ۲. اضافه کردن هش توکن به سِت دستگاه‌های فعال کاربر
        Redis::sadd("user_tokens:{$user->id}", $tokenHash);

        return [
            'token'            => $plainToken, // فقط همین یک بار به کاربر داده می‌شود
            'token_type'       => 'Bearer',
            'token_expires_at' => $expiresAt->toDateTimeString(),
            'device_id'        => $sessionData['device_id'],
        ];
    }

    /**
     * ابطال یک توکن (با توکن خام یا هش)
     */
    public function revokeToken(string $plainOrHashedToken): bool
    {
        // اگه طولش ۶۴ باشه و hex معتبر باشه، احتمالاً هش هست
        // ولی برای اطمینان، هم به‌عنوان خام و هم به‌عنوان هش چک می‌کنیم
        $tokenHash = (strlen($plainOrHashedToken) === 64 && ctype_xdigit($plainOrHashedToken))
            ? $plainOrHashedToken
            : self::hashToken($plainOrHashedToken);

        $tokenDataRaw = Redis::get("auth_token:{$tokenHash}");
        if (!$tokenDataRaw) {
            return false;
        }

        $data   = json_decode($tokenDataRaw, true);
        $userId = $data['user_id'] ?? null;

        Redis::del("auth_token:{$tokenHash}");
        if ($userId) {
            Redis::srem("user_tokens:{$userId}", $tokenHash);
        }

        return true;
    }

    public function findTokenByDeviceId(int $userId, string $deviceId): ?array
    {
        $tokenHashes = Redis::smembers("user_tokens:{$userId}");

        foreach ($tokenHashes as $hash) {
            $raw = Redis::get("auth_token:{$hash}");
            if (!$raw) {
                Redis::srem("user_tokens:{$userId}", $hash);
                continue;
            }

            $data = json_decode($raw, true);
            if (($data['device_id'] ?? null) === $deviceId) {
                return ['hash' => $hash, 'data' => $data];
            }
        }

        return null;
    }
}
