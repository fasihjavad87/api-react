<?php

// namespace App\Models;

// // use Illuminate\Contracts\Auth\MustVerifyEmail;
// use Database\Factories\UserFactory;
// use Illuminate\Database\Eloquent\Attributes\Fillable;
// use Illuminate\Database\Eloquent\Attributes\Hidden;
// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Foundation\Auth\User as Authenticatable;
// use Illuminate\Notifications\Notifiable;

// #[Fillable(['name', 'email', 'password'])]
// #[Hidden(['password', 'remember_token'])]
// class User extends Authenticatable
// {
//     /** @use HasFactory<UserFactory> */
//     use HasFactory, Notifiable;

//     /**
//      * Get the attributes that should be cast.
//      *
//      * @return array<string, string>
//      */
//     protected function casts(): array
//     {
//         return [
//             'email_verified_at' => 'datetime',
//             'password' => 'hashed',
//         ];
//     }
// }

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens;

    // فیلدهایی که می‌توانند پر شوند
    protected $fillable = [
        'name',
        'email',
        'username',
        'email_verified_at',
        'avatar',
        'is_admin',
        'status',
        'password',
    ];

    // فیلدهایی که در خروجی JSON نشان داده نشوند
    protected $hidden = [
        'password', // رو حذف کردیم چون اصلاً وجود نداره
        // 'remember_token',
    ];

    protected $appends = [
        'avatar_url',
    ];

    // فیلدهایی که باید به نوع خاصی تبدیل شوند
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'deleted_at' => 'datetime',
            'is_admin' => 'boolean',
            'password' => 'hashed'
        ];
    }


    public function getAvatarUrlAttribute()
    {
        return $this->avatar ? asset('storage/' . $this->avatar) : asset('storage/1.webp');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles->contains('name', $roleName);
    }

    public function hasPermission(string $permissionName): bool
    {
        return $this->roles->flatMap->permissions->contains('name', $permissionName);
    }

    public function hasAnyPermissions(array $permissions): bool
    {
        return $this->roles->flatMap->permissions->whereIn('name', $permissions)->isNotEmpty();
    }

    public function hasAllPermissions(array $permissions): bool
    {
        $userPermissions = $this->roles->flatMap->permissions->pluck('name')->unique();
        return collect($permissions)->diff($userPermissions)->isEmpty();
    }

    public function assignRole($roleId)
    {
        // $role = Role::where('name', $roleName)->firstOrFail();
        $this->roles()->syncWithoutDetaching([$roleId]);

        return $this;
    }

    public function assignRoles(array $roleIds)
    {
        // $roleIds = Role::whereIn('name', $roleNames)->pluck('id')->toArray();
        $this->roles()->syncWithoutDetaching($roleIds);

        return $this;
    }

    public function removeRole($roleId)
    {
        // $role = Role::where('name', $roleName)->firstOrFail();
        $this->roles()->detach($roleId);

        return $this;
    }

    public function removeRoles(array $roleIds)
    {
        // $roleIds = Role::where('name', $roleNames)->pluck('id')->toArray();
        $this->roles()->detach($roleIds);

        return $this;
    }

    public function syncRoles(array $roleIds)
    {
        // $roleIds = Role::where('name', $roleNames)->pluck('id')->toArray();
        $this->roles()->sync($roleIds);

        return $this;
    }


    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }


    protected static function booted()
    {
        static::forceDeleted(function ($user) {
            $user->tokens()->delete();
            $user->roles()->detach();
            Storage::disk('public')->delete($user->avatar);
        });
    }
}
