<?php

namespace App\Modules\SuperAdmin\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class SuperAdmin extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'super_admins';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'password',
        'role_id',
        'is_active',
        'status',
        'mfa_enabled',
        'mfa_secret',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'mfa_enabled' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function role()
    {
        return $this->belongsTo(SuperAdminRole::class, 'role_id');
    }

    public function hasRole(string $roleSlug): bool
    {
        return $this->role && $this->role->slug === $roleSlug;
    }

    public function hasPermission(string $permissionSlug): bool
    {
        if ($this->hasRole('platform_owner') || $this->hasRole('super_admin')) {
            return true;
        }

        if (!$this->role) {
            return false;
        }

        return $this->role->permissions()->where('slug', $permissionSlug)->exists();
    }
}
