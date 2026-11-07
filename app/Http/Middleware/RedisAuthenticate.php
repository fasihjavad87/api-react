<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class RedisAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'لطفا ابتدا وارد حساب خود شوید.'], 401);
        }

        // هش کردن توکن دریافتی و استفاده از اون به‌عنوان کلید Redis
        $tokenHash = hash('sha256', $token);
        $key       = "auth_token:{$tokenHash}";

        // بررسی توکن در Redis
        $sessionRaw = Redis::get($key);
        if (!$sessionRaw) {
            return response()->json(['message' => 'نشست شما منقضی یا نامعتبر شده است.'], 401);
        }

        $sessionData = json_decode($sessionRaw, true);

        if (!is_array($sessionData) || empty($sessionData['user_id'])) {
            return response()->json(['message' => 'اطلاعات نشست نامعتبر است.'], 401);
        }

        $user = User::find($sessionData['user_id']);

        if (!$user) {
            return response()->json(['message' => 'کاربر یافت نشد.'], 401);
        }

        // آپدیت سریع زمان آخرین فعالیت در حافظه رم (بدون درگیری MySQL)

        $now = now()->timestamp;
        $shouldUpdate = empty($sessionData['last_active'])
            || ($now - $sessionData['last_active']) >= 60; // هر ۶۰ ثانیه یکبار

        if ($shouldUpdate) {
            $sessionData['last_active'] = $now;
            $ttl = Redis::ttl($key);
            if ($ttl > 0) {
                Redis::setex($key, $ttl, json_encode($sessionData));
            }
        }

        // $sessionData['last_active'] = now()->timestamp;
        // $ttl = Redis::ttl($key);
        // if ($ttl > 0) {
        //     Redis::setex($key, $ttl, json_encode($sessionData));
        // }

        // ست کردن کاربر روی کانتکست لاراول تا Auth::user() و $request->user() و CheckPermission کار کنند
        Auth::setUser($user);
        $request->setUserResolver(fn() => $user);

        return $next($request);
    }
}
