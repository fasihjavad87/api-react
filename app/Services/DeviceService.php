<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

class DeviceService
{
    /**
     * بازیابی یا ساخت device_id از کوکی
     */
    public function getOrCreateDeviceId(Request $request): array
    {
        $deviceId = $request->cookie('device_id');
        $isNewCookie = false;

        if (! $deviceId || ! Str::isUuid($deviceId)) {
            $deviceId = (string) Str::uuid();
            $isNewCookie = true;
        }

        return [$deviceId, $isNewCookie];
    }

    /**
     * ساخت یا بروزرسانی دستگاه در دیتابیس
     */
    public function trackDevice(User $user, Request $request, string $deviceId): UserDevice
    {
        $info = $this->parseAgent($request);

        return UserDevice::updateOrCreate(
            [
                'user_id'   => $user->id,
                'device_id' => $deviceId,
            ],
            [
                'device_name' => $info['device_name'],
                'platform'    => $info['platform'],
                'browser'     => $info['browser'],
                'device_type' => $info['device_type'],
                'updated_at'  => now(),
            ]
        );
    }

    /**
     * استخراج سیستم‌عامل، مرورگر و نوع دستگاه با Jenssegers/Agent
     */
    protected function parseAgent(Request $request): array
    {
        $agent = new Agent();
        $agent->setUserAgent($request->userAgent());
        $agent->setHttpHeaders($request->server());

        $platform = $agent->platform() ?: 'Unknown OS';
        $browser = $agent->browser() ?: 'Unknown Browser';

        // تشخیص نوع دیوایس مطابق با Enum مایگریشن (desktop, mobile, tablet)
        $deviceType = 'desktop';

        if ($agent->isRobot()) {
            $deviceType = 'other'; // یا 'bot'
        } elseif ($agent->isTablet()) {
            $deviceType = 'tablet';
        } elseif ($agent->isMobile()) {
            $deviceType = 'mobile';
        } elseif (! $agent->isDesktop()) {
            $deviceType = 'other';
        }

        return [
            'platform'    => $platform,
            'browser'     => $browser,
            'device_type' => $deviceType,
            'device_name' => "{$browser} on {$platform}",
        ];
    }
}
