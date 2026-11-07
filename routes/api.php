<?php

use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\CategoryController;
use App\Http\Controllers\api\CountryController;
use App\Http\Controllers\api\DeviceController;
use App\Http\Controllers\api\EpisodeController;
use App\Http\Controllers\api\MediaController;
use App\Http\Controllers\api\MovieController;
use App\Http\Controllers\api\PermissionController;
use App\Http\Controllers\api\RoleController;
use App\Http\Controllers\api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::get('movies/{slug}/user', [MovieController::class, 'showUser']);

    //Login , Register , Get User , Logout
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/admin/login', [AuthController::class, 'adminLogin']);

    Route::middleware('auth:sanctum')->group(function () {
        // Route::middleware('redis.auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/logout', [AuthController::class, 'logout']);


        Route::prefix('users')->middleware('checkPermission:view-user')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::get('/{id}', [UserController::class, 'show'])->whereNumber('id');
            Route::post('/create', [UserController::class, 'store'])->middleware('checkPermission:create-user');
            Route::put('/{id}/edit', [UserController::class, 'update'])->middleware('checkPermission:update-user')->whereNumber('id');
            Route::delete('/{id}/delete', [UserController::class, 'destroy'])->middleware('checkPermission:delete-user')->whereNumber('id');
            Route::get('/trashed', [UserController::class, 'trashed'])->middleware('checkPermission:view-delete-user');
            Route::post('/{id}/restore', [UserController::class, 'restore'])->middleware('checkPermission:restore-user')->whereNumber('id');
            Route::delete('/{id}/force-delete', [UserController::class, 'forceDelete'])->middleware('checkPermission:force-delete-user')->whereNumber('id');
            Route::delete('/group-delete', [UserController::class, 'groupDelete'])->middleware('checkPermission:delete-user');
            Route::delete('/group-restore', [UserController::class, 'groupRestore'])->middleware('checkPermission:restore-user');
            Route::delete('/group-force-delete', [UserController::class, 'groupForceDelete'])->middleware('checkPermission:force-delete-user');
            Route::put('/{id}/roles', [UserController::class, 'syncRoles'])->middleware('checkPermission:sync-role-to-user')->whereNumber('id');
        });

        Route::prefix('roles')->middleware('checkPermission:view-role')->group(function () {
            Route::get('/', [RoleController::class, 'index']);
            Route::get('/all', [RoleController::class, 'getAll']);
            Route::get('/{id}', [RoleController::class, 'show'])->whereNumber('id');
            Route::post('/create', [RoleController::class, 'store'])->middleware('checkPermission:create-role');
            Route::put('/{id}/edit', [RoleController::class, 'update'])->middleware('checkPermission:update-role')->whereNumber('id');
            Route::delete('/{id}/delete', [RoleController::class, 'destroy'])->middleware('checkPermission:delete-role')->whereNumber('id');
            Route::put('/{id}/permissions', [RoleController::class, 'syncPermissions'])->middleware('checkPermission:sync-permission-to-role')->whereNumber('id');
        });

        Route::prefix('permissions')->middleware('checkPermission:view-permission')->group(function () {
            Route::get('/', [PermissionController::class, 'index']);
            Route::get('/all', [PermissionController::class, 'getAll']);
            Route::get('/{id}', [PermissionController::class, 'show'])->whereNumber('id');
            Route::post('/create', [PermissionController::class, 'store'])->middleware('checkPermission:create-permission');
            Route::put('/{id}/edit', [PermissionController::class, 'update'])->middleware('checkPermission:update-permission')->whereNumber('id');
            Route::delete('/{id}/delete', [PermissionController::class, 'destroy'])->middleware('checkPermission:delete-permission')->whereNumber('id');
        });

        Route::prefix('devices')->group(function () {
            Route::get('/', [DeviceController::class, 'index']);
            Route::delete('/others', [DeviceController::class, 'revokeOtherDevices']);
            Route::delete('/all', [DeviceController::class, 'revokeAllDevices']);
            Route::prefix('user/{userId}')->whereNumber('userId')->group(function () {
                Route::get('/', [DeviceController::class, 'userDevices']);
                Route::delete('/device/{deviceId}', [DeviceController::class, 'revokeUserDevice']);
                Route::delete('/all', [DeviceController::class, 'revokeAllUserDevices']);
            });
            Route::delete('/{deviceId}', [DeviceController::class, 'revokeDevice']);
        });

        Route::prefix('categories')->middleware('checkPermission:view-category')->group(function () {
            Route::get('/', [CategoryController::class, 'index']);
            Route::get('/all', [CategoryController::class, 'getAll']);
            Route::get('/{id}', [CategoryController::class, 'show'])->whereNumber('id');
            Route::post('/create', [CategoryController::class, 'store'])->middleware('checkPermission:create-category');
            Route::put('/{id}/edit', [CategoryController::class, 'update'])->middleware('checkPermission:update-category')->whereNumber('id');
            Route::delete('/{id}/delete', [CategoryController::class, 'destroy'])->middleware('checkPermission:delete-category')->whereNumber('id');
        });

        Route::prefix('countries')->middleware('checkPermission:view-country')->group(function () {
            Route::get('/', [CountryController::class, 'index']);
            Route::get('/all', [CountryController::class, 'getAll']);
            Route::get('/{id}', [CountryController::class, 'show'])->whereNumber('id');
            Route::post('/create', [CountryController::class, 'store'])->middleware('checkPermission:create-country');
            Route::put('/{id}/edit', [CountryController::class, 'update'])->middleware('checkPermission:update-country')->whereNumber('id');
            Route::delete('/{id}/delete', [CountryController::class, 'destroy'])->middleware('checkPermission:delete-country')->whereNumber('id');
        });


        Route::prefix('media')->middleware('checkPermission:view-media')->group(function () {
            // لیست
            Route::get('/', [MediaController::class, 'index']);
            // سبد حذف
            Route::get('/trashed', [MediaController::class, 'trashed'])->middleware('checkPermission:view-delete-media');
            // عملیات گروهی (با whereNumber روی /{id} ترتیب مهم نیست، ولی برای خوانایی بهتره اول بیان)
            Route::delete('/group-delete', [MediaController::class, 'groupDelete'])->middleware('checkPermission:delete-media');
            Route::delete('/group-restore', [MediaController::class, 'groupRestore'])->middleware('checkPermission:restore-media');
            Route::delete('/group-force-delete', [MediaController::class, 'groupForceDelete'])->middleware('checkPermission:force-delete-media');
            // آپلود
            Route::post('/create', [MediaController::class, 'store'])->middleware('checkPermission:create-media');
            // یک رسانه
            Route::get('/{id}', [MediaController::class, 'show'])->whereNumber('id');
            Route::put('/{id}/edit', [MediaController::class, 'update'])->middleware('checkPermission:update-media')->whereNumber('id');
            Route::delete('/{id}/delete', [MediaController::class, 'destroy'])->middleware('checkPermission:delete-media')->whereNumber('id');
            Route::post('/{id}/restore', [MediaController::class, 'restore'])->middleware('checkPermission:restore-media')->whereNumber('id');
            Route::delete('/{id}/force-delete', [MediaController::class, 'forceDelete'])->middleware('checkPermission:force-delete-media')->whereNumber('id');
        });

        Route::prefix('movies')->middleware('checkPermission:view-movie')->group(function () {
            Route::get('/', [MovieController::class, 'index']);
            Route::post('/create', [MovieController::class, 'store'])->middleware('checkPermission:create-movie');
            Route::get('/{id}', [MovieController::class, 'show'])->whereNumber('id');
            Route::put('/{id}/edit', [MovieController::class, 'update'])->middleware('checkPermission:update-movie')->whereNumber('id');
            Route::delete('/{id}/delete', [MovieController::class, 'destroy'])->middleware('checkPermission:delete-movie')->whereNumber('id');

            Route::get('{id}/media', [MovieController::class, 'getMedia']);
            Route::post('{id}/media', [MovieController::class, 'syncMedia']);

            Route::prefix('{id}/episodes')->whereNumber('id')->group(function () {
                Route::get('/', [EpisodeController::class, 'index']);
                Route::post('/create', [EpisodeController::class, 'store'])->middleware('checkPermission:create-episode');

                Route::post('/generate', [EpisodeController::class, 'generate'])->middleware('checkPermission:create-episode');
                Route::post('/shift', [EpisodeController::class, 'shift'])->middleware('checkPermission:update-episode');
                Route::patch('/bulk-status', [EpisodeController::class, 'bulkStatus'])->middleware('checkPermission:update-episode');
                Route::patch('/regenerate-schedule', [EpisodeController::class, 'regenerateSchedule'])->middleware('checkPermission:update-episode');

                Route::patch('/{episodeId}/edit', [EpisodeController::class, 'update'])->middleware('checkPermission:update-episode')->whereNumber('episodeId');
                Route::delete('/{episodeId}/delete', [EpisodeController::class, 'destroy'])->middleware('checkPermission:delete-episode')->whereNumber('episodeId');
                Route::patch('/{episodeId}/reschedule', [EpisodeController::class, 'reschedule'])->middleware('checkPermission:update-episode')->whereNumber('episodeId');
            });
        });
    });
});
