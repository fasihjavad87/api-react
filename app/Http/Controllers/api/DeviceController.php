<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\JsonResponse;
// use App\Services\TokenService;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Redis;

class DeviceController extends Controller
{
    // public function index(Request $request)
    // {
    //     $user         = $request->user();
    //     $currentToken = $request->bearerToken();

    //     // هش توکن فعلی برای مقایسه با لیست
    //     $currentHash = $currentToken ? TokenService::hashToken($currentToken) : null;

    //     $tokenHashes = Redis::smembers("user_tokens:{$user->id}");
    //     $devices     = [];

    //     foreach ($tokenHashes as $tokenHash) {
    //         $dataRaw = Redis::get("auth_token:{$tokenHash}");

    //         if ($dataRaw) {
    //             $item = json_decode($dataRaw, true);
    //             $devices[] = [
    //                 'device_id'   => $item['device_id'] ?? null,
    //                 'device_type' => $item['device_type'] ?? null,
    //                 'platform'    => $item['platform'] ?? null,
    //                 'browser'     => $item['browser'] ?? null,
    //                 'ip_address'  => $item['ip_address'] ?? null,
    //                 'last_active' => isset($item['last_active'])
    //                     ? date('Y-m-d H:i:s', $item['last_active'])
    //                     : null,
    //                 'is_current'  => ($tokenHash === $currentHash),
    //                 'token_hint'  => substr($tokenHash, 0, 8) . '...',
    //             ];
    //         } else {
    //             // اگر توکن در ردیس اکسپایر شده، از لیست پاکش کن
    //             Redis::srem("user_tokens:{$user->id}", $tokenHash);
    //         }
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'devices' => $devices,
    //     ]);
    // }

    // public function revokeOtherDevices(Request $request, TokenService $tokenService)
    // {
    //     $user         = $request->user();
    //     $currentToken = $request->bearerToken();
    //     $currentHash  = $currentToken ? TokenService::hashToken($currentToken) : null;

    //     $tokenHashes = Redis::smembers("user_tokens:{$user->id}");
    //     foreach ($tokenHashes as $tokenHash) {
    //         if ($tokenHash !== $currentHash) {
    //             $tokenService->revokeToken($tokenHash); // هش رو مستقیم پاس می‌دیم
    //         }
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'تمام نشست‌های دیگر با موفقیت خارج شدند.',
    //     ]);
    // }


    // public function revokeDevice(Request $request, TokenService $tokenService, string $deviceId)
    // {
    //     $user         = $request->user();
    //     $currentToken = $request->bearerToken();
    //     $currentHash  = $currentToken ? TokenService::hashToken($currentToken) : null;

    //     $found = $tokenService->findTokenByDeviceId($user->id, $deviceId);

    //     if (!$found) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'دستگاه مورد نظر یافت نشد.',
    //         ], 404);
    //     }

    //     // جلوگیری از حذف دستگاه فعلی از این route
    //     if ($found['hash'] === $currentHash) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'برای خروج از دستگاه فعلی از دکمه خروج استفاده کنید.',
    //         ], 422);
    //     }

    //     $tokenService->revokeToken($found['hash']);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'دستگاه با موفقیت خارج شد.',
    //     ]);
    // }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();
        $currentDeviceId = $currentToken?->device_id ?? $request->cookie('device_id');

        // خواندن تمام دستگاه‌های کاربر به همراه آخرین توکن‌های معتبر آن‌ها
        $devices = UserDevice::where('user_id', $user->id)
            ->get()
            ->map(function ($device) use ($user, $currentDeviceId) {
                // یافتن توکن‌های مرتبط با این دستگاه
                $tokens = $user->tokens()
                    ->where('device_id', $device->device_id)
                    ->get();

                if ($tokens->isEmpty()) {
                    return null; // دستگاهی که توکن فعالی ندارد نمایش داده نمی‌شود
                }

                // محاسبه آخرین فعالیت از روی MAX(last_used_at یا created_at)
                $lastActive = $tokens->max(fn($token) => $token->last_used_at ?? $token->created_at);

                return [
                    'device_id'    => $device->device_id,
                    'device_name'  => $device->device_name,
                    'platform'     => $device->platform,
                    'browser'      => $device->browser,
                    'device_type'  => $device->device_type,
                    'last_active'  => $lastActive ? $lastActive->toDateTimeString() : null,
                    'active_tokens_count' => $tokens->count(),
                    'is_current'   => ($device->device_id === $currentDeviceId),
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'devices' => $devices,
        ]);
    }

    /**
     * ابطال و لاگ‌اوت سایر دستگاه‌ها به جز دستگاه فعلی
     */
    public function revokeOtherDevices(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        if (! $currentToken) {
            return response()->json([
                'success' => false,
                'message' => 'نشست فعلی یافت نشد.',
            ], 400);
        }

        // حذف همه توکن‌ها به جز توکن فعلی
        $user->tokens()
            ->where('id', '!=', $currentToken->id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'تمام نشست‌های دیگر با موفقیت خارج شدند.',
        ]);
    }

    /**
     * حذف و ابطال تمام نشست‌های یک دستگاه خاص
     */
    public function revokeDevice(Request $request, string $deviceId): JsonResponse
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        // جلوگیری از حذف دستگاه فعلی با این متد
        if ($currentToken && $currentToken->device_id === $deviceId) {
            return response()->json([
                'success' => false,
                'message' => 'برای خروج از دستگاه فعلی از دکمه لاگ‌اوت استفاده کنید.',
            ], 422);
        }

        // حذف تمام توکن‌های متعلق به آن device_id
        $deleted = $user->tokens()
            ->where('device_id', $deviceId)
            ->delete();

        if ($deleted === 0) {
            return response()->json([
                'success' => false,
                'message' => 'دستگاه مورد نظر یا توکن فعالی برای آن یافت نشد.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'دستگاه با موفقیت خارج شد.',
        ]);
    }

    /**
     * خروج از تمام دستگاه‌ها (حتی دستگاه فعلی)
     */
    public function revokeAllDevices(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'خروج از تمام دستگاه‌ها با موفقیت انجام شد.',
        ]);
    }





    public function userDevices(Request $request, int $userId): JsonResponse
    {
        $user = User::find($userId);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر یافت نشد.',
            ], 404);
        }

        $devices = UserDevice::where('user_id', $user->id)
            ->get()
            ->map(function ($device) use ($user) {
                $tokens = $user->tokens()
                    ->where('device_id', $device->device_id)
                    ->get();

                if ($tokens->isEmpty()) {
                    return null;
                }

                $lastActive = $tokens->max(fn($t) => $t->last_used_at ?? $t->created_at);

                return [
                    'device_id'    => $device->device_id,
                    'device_name'  => $device->device_name,
                    'platform'     => $device->platform,
                    'browser'      => $device->browser,
                    'device_type'  => $device->device_type,
                    'last_active'  => $lastActive ? $lastActive->toDateTimeString() : null,
                    'active_tokens_count' => $tokens->count(),
                    'is_current'   => false, // از دید ادمین هیچ دستگاهی «فعلی» نیست
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'devices' => $devices,
        ]);
    }

    /**
     * ابطال یک دستگاه خاص از کاربر (ادمین)
     */
    public function revokeUserDevice(Request $request, int $userId, string $deviceId): JsonResponse
    {
        $user = User::find($userId);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر یافت نشد.',
            ], 404);
        }

        $deleted = $user->tokens()
            ->where('device_id', $deviceId)
            ->delete();

        if ($deleted === 0) {
            return response()->json([
                'success' => false,
                'message' => 'دستگاهی با این شناسه برای کاربر یافت نشد.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'دستگاه با موفقیت خارج شد.',
        ]);
    }

    /**
     * ابطال همه دستگاه‌های یک کاربر (ادمین)
     */
    public function revokeAllUserDevices(Request $request, int $userId): JsonResponse
    {
        $user = User::find($userId);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر یافت نشد.',
            ], 404);
        }

        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'همه دستگاه‌های کاربر با موفقیت خارج شدند.',
        ]);
    }
}
