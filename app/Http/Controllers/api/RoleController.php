<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\role\StoreRoleRequest;
use App\Http\Requests\role\SyncRolePermissionsRequest;
use App\Http\Requests\role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $count = $request->count ?? 10;
        $search = $request->searchChar;

        $roles = Role::query()
            ->when($search, function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            })
            ->paginate($count);

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }

    public function getAll()
    {
        $roles = Role::get();

        return response()->json([
            'success' => true,
            'data' => RoleResource::collection($roles)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request)
    {
        $validated = $request->validated();

        $role = Role::create($validated);

        if (array_key_exists('permissions', $validated)) {
            $role->syncPermissions($validated['permission']);
        }

        return response()->json([
            'success' => true,
            'message' => 'User Created Successfully',
            'data' => new RoleResource($role->load('permissions')),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new RoleResource($role->load('permissions')),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, string $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $validated = $request->validated();

        $role->update($validated);

        if (array_key_exists('permissions', $validated)) {
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully',
            'data' => new RoleResource($role->load('permissions')),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully'
        ]);
    }


    public function syncPermissions(SyncRolePermissionsRequest $request, $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validated();

        $role->syncPermissions($validated['permissions']);

        return response()->json([
            'success' => true,
            'message' => 'User roles updated successfully.',
            'data' => new RoleResource(
                $role->fresh()->load('permissions')
            ),
        ]);
    }
}