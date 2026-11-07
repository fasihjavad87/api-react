<?php

// namespace App\Http\Controllers\api;

// use App\Http\Controllers\Controller;
// use App\Http\Resources\UserResource;
// use App\Models\User;
// use App\Services\TokenService;
// use Carbon\Carbon;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Hash;

// class AuthController extends Controller
// {


// protected TokenService $tokenService;

//     public function __construct(TokenService $tokenService)
//     {
//         $this->tokenService = $tokenService;
//     }



    // public function register(Request $request)
    // {
    //     // return response()->json([
    //     //     'message'=>'send request'
    //     // ]);
    //     $registerUserData = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|max:255|email|unique:users,email',
    //         'password' => 'required|string|min:8|confirmed'
    //     ]);

    //     $user = User::create([
    //         'name' => $registerUserData['name'],
    //         'email' => $registerUserData['email'],
    //         'password' => $registerUserData['password']
    //     ]);

    //     $tokenResult = $this->tokenService->createToken($user, $request, false);

    //     return response()->json([
    //         'success'          => true,
    //         'token'            => $tokenResult['token'],
    //         'token_type'       => $tokenResult['token_type'],
    //         'token_expires_at' => $tokenResult['token_expires_at'],
    //         'device_id'        => $tokenResult['device_id'],
    //         'user'             => new UserResource($user),
    //     ], 201);

        // $token = $user->createToken('AuthToken');
        // $token->accessToken->expires_at = Carbon::now()->addDay(10);
        // $token->accessToken->save();

        // return response()->json([
        //     'success' => true,
        //     'token' => $token->plainTextToken,
        //     'token_type' => 'Bearer',
        //     'token_expires_at' => Carbon::parse($token->accessToken->expires_at)->toDateTimeString(),
        //     'user' => new UserResource($user),
        // ], 200);
    // }

    // public function login(Request $request)
    // {

    //     $loginUserData = $request->validate([
    //         'email' => 'required|email|max:255',
    //         'password' => 'required|string|min:8',
    //         'remember' => 'nullable|boolean'
    //     ]);

    //     $user = User::query()->with('roles.permissions')->where('email', $loginUserData['email'])->first();
    //     if (!$user || !Hash::check($loginUserData['password'], $user->password)) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Invalid Credentials'
    //         ], 401);
    //     }

    //     $remember = $loginUserData['remember'] ?? false;
    //     $tokenResult = $this->tokenService->createToken($user, $request, $remember);

    //     return response()->json([
    //         'success'          => true,
    //         'token'            => $tokenResult['token'],
    //         'token_type'       => $tokenResult['token_type'],
    //         'token_expires_at' => $tokenResult['token_expires_at'],
    //         'device_id'        => $tokenResult['device_id'],
    //         'user'             => new UserResource($user),
    //     ], 200);

        // $token = $user->createToken('AuthToken');
        // if ($loginUserData['remember'] ?? false) {
        //     $token->accessToken->expires_at = Carbon::now()->addMonth(3);
        // } else {
        //     $token->accessToken->expires_at = Carbon::now()->addDay(5);
        // }
        // $token->accessToken->save();

        // return response()->json([
        //     'success' => true,
        //     'token' => $token->plainTextToken,
        //     'token_type' => 'Bearer',
        //     'token_expires_at' => Carbon::parse($token->accessToken->expires_at)->toDateTimeString(),
        //     'user' => new UserResource($user),
        // ], 200);
    // }
    
    // public function adminLogin(Request $request)
    // {

    //     $loginUserData = $request->validate([
    //         'email' => 'required|email|max:255',
    //         'password' => 'required|string|min:8',
    //         'remember' => 'nullable|boolean'
    //     ]);

    //     $user = User::query()->with('roles.permissions')->where('email', $loginUserData['email'])->first();
    //     if (!$user || !Hash::check($loginUserData['password'], $user->password)) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Invalid Credentials'
    //         ], 401);
    //     }
        
    //     if (!$user->is_admin) {
    //         return response()->json([
    //            'success' => false,
    //            'message' => 'شما اجازه ورود به پنل مدیریت را ندارید.' 
    //         ], 403);
    //     }

    //     $remember = $loginUserData['remember'] ?? false;
    //     $tokenResult = $this->tokenService->createToken($user, $request, $remember);

    //     return response()->json([
    //         'success'          => true,
    //         'token'            => $tokenResult['token'],
    //         'token_type'       => $tokenResult['token_type'],
    //         'token_expires_at' => $tokenResult['token_expires_at'],
    //         'device_id'        => $tokenResult['device_id'],
    //         'user'             => new UserResource($user),
    //     ], 200);

        // $token = $user->createToken('AuthToken');
        // if ($loginUserData['remember'] ?? false) {
        //     $token->accessToken->expires_at = Carbon::now()->addMonth(3);
        // } else {
        //     $token->accessToken->expires_at = Carbon::now()->addDay(5);
        // }
        // $token->accessToken->save();

        // return response()->json([
        //     'success' => true,
        //     'token' => $token->plainTextToken,
        //     'token_type' => 'Bearer',
        //     'token_expires_at' => Carbon::parse($token->accessToken->expires_at)->toDateTimeString(),
        //     'user' => new UserResource($user),
        // ], 200);
    // }

    // public function logout(Request $request)
    // {
    //     // $request->user()->currentAccessToken()->delete();
    //     $token = $request->bearerToken();
    //     if ($token) {
    //         $this->tokenService->revokeToken($token);
    //     }
    //     return response()->json([
    //         'success' => true,
    //         'message' => 'logged out',
    //     ]);
    // }

    // public function me(Request $request)
    // {
    //     // $user = Auth::user();

    //     return response()->json([
    //         'success' => true,
    //         'user' => new UserResource($request->user()->load('roles.permissions')),
    //         // 'user'=>$request->user()->load('roles.permissions')
    //     ]);
    // }
// }



namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\DeviceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        protected DeviceService $deviceService
    ) {}

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|max:255|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'],
        ]);

        return $this->respondWithToken($user, $request, false, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => 'required|email|max:255',
            'password' => 'required|string|min:8',
            'remember' => 'nullable|boolean',
        ]);

        $user = User::with('roles.permissions')->where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات ورود نامعتبر است.',
            ], 401);
        }

        $remember = $data['remember'] ?? false;

        return $this->respondWithToken($user, $request, $remember, 200);
    }

    public function adminLogin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => 'required|email|max:255',
            'password' => 'required|string|min:8',
            'remember' => 'nullable|boolean',
        ]);

        $user = User::with('roles.permissions')->where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات ورود نامعتبر است.',
            ], 401);
        }

        if (! $user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'شما اجازه ورود به پنل مدیریت را ندارید.',
            ], 403);
        }

        $remember = $data['remember'] ?? false;

        return $this->respondWithToken($user, $request, $remember, 200);
    }

    public function logout(Request $request): JsonResponse
    {
        // حذف توکن فعلی کاربر از دیتابیس
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'با موفقیت خارج شدید.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'user'    => new UserResource($request->user()->load('roles.permissions')),
        ]);
    }

    /**
     * صدور توکن Sanctum و تنظیم کوکی device_id
     */
    protected function respondWithToken(User $user, Request $request, bool $remember, int $status = 200): JsonResponse
    {
        [$deviceId, $isNewCookie] = $this->deviceService->getOrCreateDeviceId($request);

        // ثبت یا به‌روزرسانی اطلاعات سیستم/مرورگر دستگاه
        $this->deviceService->trackDevice($user, $request, $deviceId);

        // تنظیم انقضای ستون expires_at بر اساس Remember Me
        $expiresAt = $remember ? now()->addMonths(3) : now()->addDays(5);

        // ایجاد توکن از طریق Sanctum
        $newAccessToken = $user->createToken(
            name: $request->header('User-Agent', 'Default Device'),
            abilities: ['*'],
            expiresAt: $expiresAt
        );

        // ذخیره device_id روی سطر personal_access_tokens
        $newAccessToken->accessToken->forceFill([
            'device_id'    => $deviceId,
            'last_used_at' => now(),
        ])->save();

        $response = response()->json([
            'success'          => true,
            'token'            => $newAccessToken->plainTextToken,
            'token_type'       => 'Bearer',
            'token_expires_at' => $expiresAt->toDateTimeString(),
            'device_id'        => $deviceId,
            'user'             => new UserResource($user),
        ], $status);

        // تنظیم کوکی دائمی ۵ ساله برای مرورگر
      if ($isNewCookie || ! $request->hasCookie('device_id')) {
            $response->withCookie(
                cookie()->forever(
                    name: 'device_id',
                    value: $deviceId,
                    path: '/',
                    domain: null,
                    secure: true,
                    httpOnly: true,
                    raw: false,
                    sameSite: 'none'
                )
            );
        }

        return $response;
    }
}