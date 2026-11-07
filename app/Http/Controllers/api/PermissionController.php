<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\permission\StorePermissionRequest;
use App\Http\Requests\permission\UpdatePermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $count = $request->count ?? 10;
        $search = $request->searchChar;

        $permissions = Permission::query()
            ->when($search, function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            })
            ->paginate($count);

        return response()->json([
            'success' => true,
            'data' => $permissions,
        ]);
    }

    public function getAll()
    {
        $permissions = permission::get();

        return response()->json([
            'success' => true,
            'data' => PermissionResource::collection($permissions)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePermissionRequest $request)
    {
        $validated = $request->validated();

        $permission = Permission::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Permission Created Successfully',
            'data' => new PermissionResource($permission),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json(['message' => 'Permission not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new PermissionResource($permission),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePermissionRequest $request, string $id)
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json(['message' => 'Permission not found'], 404);
        }

        $validated = $request->validated();

        $permission->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Permission updated successfully',
            'data' => new PermissionResource($permission),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $permisson = Permission::find($id);

        if (!$permisson) {
            return response()->json(['message' => 'Permission not found'], 404);
        }

        $permisson->delete();

        return response()->json([
            'success' => true,
            'message' => 'Permission deleted successfully'
        ]);
    }
}