<?php

namespace App\Http\Controllers\api;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\user\groupDeleteUserRequest;
use App\Http\Requests\user\groupForceDeleteUserRequest;
use App\Http\Requests\user\groupRestoreUserRequest;
use App\Http\Requests\user\StoreUserRequest;
use App\Http\Requests\user\SyncUserRolesRequest;
use App\Http\Requests\user\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class userController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function index()
    // {
    //     $users = User::query()->with('roles.permissions')->paginate(10);
    //     return response()->json([
    //         'success' => true,
    //         'data' => $users,
    //         // 'data' => UserResource::collection($users),
    //     ]);
    // }

    public function index(Request $request)
    {
        $count = $request->input('count', 10);
        $searchChar = $request->input('searchChar', '');

        $users = User::query()
            ->with('roles.permissions')
            ->when($searchChar, function ($query) use ($searchChar) {
                $query->where('name', 'like', "%{$searchChar}%")
                    ->orWhere('email', 'like', "%{$searchChar}%")
                    ->orWhere('username', 'like', "%{$searchChar}%");
            })
            ->paginate($count);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {

        // return response()->json([
        //     'ok' => true
        // ]);

        // return response()->json([
        //     'message' => 'send request'
        // ]);
        // $existingUser = User::where('email', $request->email)->first();

        // if ($existingUser) {
        //     return response()->json([
        //         'error' => 'A user with this email already exists',
        //         'user_id' => $existingUser->id,
        //         'email' => $existingUser->email
        //     ], 409); // 409 Conflict - وضعیت مناسب برای منابع تکراری
        // }
        // $user = User::create([
        //     'name' => $request->name,
        //     'username' => $request->username,
        //     'email' => $request->email,
        // ]);

        // $validated = $request->validate([
        //     'name' => 'required|string|max:255',
        //     'username' => 'nullable|string|unique:users,username|max:255',
        //     'email' => 'required|email|unique:users,email|max:255',
        //     'password' => 'required|string|min:8',
        //     'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        //     'is_admin' => 'sometimes|boolean',
        //     'status' => ['sometimes', 'string', Rule::in(array_column(UserStatus::cases(), 'value'))],
        // ]);





        $validated = $request->validated();


        if ($request->hasFile('avatar')) {

            $validated['avatar'] = FileUploadService::upload($request->file('avatar'), 'avatar');
        }

        $user = User::create($validated);

        if (array_key_exists('roles', $validated)) {
            $user->syncRoles($validated['roles']);
        }
        // $user->load('roles.permissions');

        return response()->json([
            'success' => true,
            'message' => 'User Created Successfully',
            'data' => new UserResource($user->load('roles.permissions')),
        ]);




        // $validated = $request->validate([
        //     'name' => 'required|string|max:255',
        //     'username' => 'required|string|unique:users,username|max:255',
        //     'email' => 'required|email|unique:users,email|max:255'
        // ], [
        //     // پیام‌های سفارشی
        //     'email.unique' => 'This email is already registered. User exists!',
        //     'username.unique' => 'This username is taken. Please choose another one.',
        //     'name.required' => 'Name field is required.',
        // ]);

        // $user = User::create($validated);

        // return response()->json([
        //     'massage' => 'User created successfully',
        //     'data' => $user
        // ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new UserResource($user->load('roles.permissions')),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, string $id)
    {
        $user = User::query()->find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if (!auth()->user()->is_admin && ($request->has('is_admin') || $request->has('status'))) {
            return response()->json([
                'message' => 'Only admins can change is_admin or status'
            ], 403);
        }

        $validated = $request->validated();
        // $validated = $request->validate([
        //     'name' => 'sometimes|string|max:255',
        //     'username' => 'sometimes|string|unique:users,username,' . $id . '|max:255',
        //     'email' => 'sometimes|email|unique:users,email,' . $id . '|max:255',
        //     'avatar' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        //     'is_admin' => 'sometimes|boolean',
        //     'status' => ['sometimes', 'string', Rule::in(array_column(UserStatus::cases(), 'value'))],
        // ]);

        if ($request->hasFile('avatar')) {

            if ($user->avatar) {
                FileUploadService::delete($user->avatar, 'public');
            }

            $validated['avatar'] = FileUploadService::upload($request->file('avatar'), 'avatar');
        }

        $user->update($validated);

        if (array_key_exists('roles', $validated)) {
            $user->syncRoles($validated['roles']);
        }

        // $user->update([
        //     'name' => $request->name,
        //     'username' => $request->username,
        //     'email' => $request->email
        // ]);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => new UserResource($user->load('roles.permissions')),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::query()->find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    public function groupDelete(groupDeleteUserRequest $request)
    {
        // اعتبارسنجی
        $validated = $request->validated();

        $ids = $request->input('ids');

        try {
            // حذف دسته‌جمعی کاربران
            $deletedCount = User::whereIn('id', $ids)->delete();

            // بررسی اینکه چند کاربر حذف شدن
            $notFoundCount = count($ids) - $deletedCount;

            return response()->json([
                'success' => true,
                'message' => "{$deletedCount} کاربر با موفقیت به زباله‌دان منتقل شدند" .
                    ($notFoundCount > 0 ? " و {$notFoundCount} کاربر یافت نشد" : ""),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف دسته‌جمعی کاربران',
                // 'error' => $e->getMessage()
            ], 500);
        }
    }

    // public function trashed()
    // {
    //     $users = User::onlyTrashed()->paginate(10);

    //     if (!$users) {
    //        return response()->json([
    //         'message'=>'kkjdikls'
    //        ]);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'data' => UserResource::collection($users),
    //     ]);
    // }

    public function trashed(Request $request)
    {
        $count = $request->input('count', 10);
        $searchChar = $request->input('searchChar', '');

        $users = User::onlyTrashed()
            // ->with('roles.permissions')
            ->when($searchChar, function ($query) use ($searchChar) {
                $query->where('name', 'like', "%{$searchChar}%")
                    ->orWhere('email', 'like', "%{$searchChar}%")
                    ->orWhere('username', 'like', "%{$searchChar}%");
            })
            ->paginate($count);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    public function restore($id)
    {
        $user = User::onlyTrashed()->find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User Not Found',
            ], 404);
        }

        $user->restore();

        return response()->json([
            'success' => true,
            'message' => 'User Restored Successfully',
            'data' => new UserResource($user),
        ]);
    }

    public function groupRestore(groupRestoreUserRequest $request)
    {
        $validated = $request->validated();

        if ($validated->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                // 'errors' => $validated->errors()
            ], 422);
        }

        $ids = $request->input('ids');

        try {
            $restoredCount = User::onlyTrashed()->whereIn('id', $ids)->restore();

            return response()->json([
                'success' => true,
                'message' => "{$restoredCount} کاربر با موفقیت بازیابی شدند",
                // 'data' => [
                //     'restored_count' => $restoredCount,
                //     'restored_ids' => $ids
                // ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در بازیابی دسته‌جمعی کاربران',
                // 'error' => $e->getMessage()
            ], 500);
        }
    }

    public function forceDelete($id)
    {
        $user = User::onlyTrashed()->find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User Not Found',
            ], 404);
        }

        // if ($user->avatar) {
        //     Storage::disk('public')->delete($user->avatar);
        // }

        $user->forceDelete();
        return response()->json([
            'success' => true,
            'message' => 'User Permanently Deleted',
        ]);
    }

    public function groupForceDelete(groupForceDeleteUserRequest $request)
    {
        $validated = $request->validated();

        if ($validated->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                // 'errors' => $validated->errors()
            ], 422);
        }

        $ids = $request->input('ids');

        try {
            $deletedCount = User::onlyTrashed()->whereIn('id', $ids)->forceDelete();

            return response()->json([
                'success' => true,
                'message' => "{$deletedCount} کاربر با موفقیت حذف دائمی شدند",
                // 'data' => [
                //     'deleted_count' => $deletedCount,
                //     'deleted_ids' => $ids
                // ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف دائمی دسته‌جمعی کاربران',
                // 'error' => $e->getMessage()
            ], 500);
        }
    }




    public function syncRoles(SyncUserRolesRequest $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validated();

        $user->syncRoles($validated['roles']);

        return response()->json([
            'success' => true,
            'message' => 'User roles updated successfully.',
            'data' => new UserResource(
                $user->fresh()->load('roles.permissions')
            ),
        ]);
    }
}