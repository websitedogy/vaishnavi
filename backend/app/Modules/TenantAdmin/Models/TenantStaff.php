<?php

namespace App\Modules\TenantAdmin\Models;

use App\Models\User;
use App\Modules\SuperAdmin\Models\Tenant;

class TenantStaff extends User
{
    protected $table = 'users';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role', // owner, admin, editor, sub_editor, designer, reporter, viewer
        'is_active',
        'permissions',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'permissions' => 'array',
        'email_verified_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->role === 'owner' || $this->role === 'admin') {
            return true;
        }

        $perms = $this->permissions ?? [];
        if (in_array('*', $perms, true)) {
            return true;
        }

        foreach ($perms as $p) {
            if ($p === $permission || (str_ends_with($p, '.*') && str_starts_with($permission, substr($p, 0, -2)))) {
                return true;
            }
        }

        return false;
    }
}
