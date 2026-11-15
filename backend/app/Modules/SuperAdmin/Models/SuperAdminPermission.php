<?php

namespace App\Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Model;

class SuperAdminPermission extends Model
{
    protected $table = 'super_admin_permissions';

    protected $fillable = [
        'name',
        'slug',
        'module',
        'description',
    ];

    public function roles()
    {
        return $this->belongsToMany(
            SuperAdminRole::class,
            'super_admin_role_permissions',
            'permission_id',
            'role_id'
        );
    }
}
