<?php

namespace App\Models;

use App\Enums\TenantStatus;
use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'role',
        'permissions',
        'name',
        'email',
        'password',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
        'role' => UserRole::class,
        'permissions' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function resolvedPermissions(): array
    {
        $defaults = $this->role?->defaultPermissions() ?? UserRole::Reporter->defaultPermissions();
        $custom = is_array($this->permissions) ? $this->permissions : [];

        return array_merge($defaults, $custom);
    }

    public function hasPermission(string $key): bool
    {
        return (bool) ($this->resolvedPermissions()[$key] ?? false);
    }

    public function isTenantAdmin(): bool
    {
        return $this->tenant_id !== null && $this->role === UserRole::Admin;
    }

    public function isTenantStaff(): bool
    {
        return $this->tenant_id !== null && in_array($this->role, [UserRole::Admin, UserRole::Editor, UserRole::Reporter], true);
    }

    public function canAccessTenantPanel(): bool
    {
        if (! $this->is_active || ! $this->isTenantStaff()) {
            return false;
        }

        $tenant = $this->tenant;

        if (! $tenant) {
            return false;
        }

        return in_array($tenant->status, [TenantStatus::Active, TenantStatus::Trial], true);
    }

    public function sessions()
    {
        return $this->hasMany(UserSession::class);
    }

    public function newsPosts()
    {
        return $this->hasMany(NewsPost::class, 'author_id');
    }
}
