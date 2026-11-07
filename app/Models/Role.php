<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'name',
        'title',
        'description',
        'is_active',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    public function givePermissionTo(string $permissionId)
    {
        // $permission = Permission::where('name', $permissionName)->firstOrFail();
        $this->permissions()->syncWithoutDetaching([$permissionId]);

        return $this;
    }

    public function revokePermissionTo(string $permissionId)
    {
        // $permission = Permission::where('name', $permissionName)->firstOrFail();
        $this->permissions()->detach($permissionId);

        return $this;
    }

     public function syncPermissions(array $permissionIds)
    {
        // $roleIds = Role::where('name', $roleNames)->pluck('id')->toArray();
        $this->permissions()->sync($permissionIds);

        return $this;
    }

    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions->contains('name', $permissionName);
    }
    
}