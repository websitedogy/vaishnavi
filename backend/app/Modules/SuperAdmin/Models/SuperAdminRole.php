<?php

namespace App\Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Model;

class SuperAdminRole extends Model
{
    protected $table = 'super_admin_roles';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function permissions()
    {
        return $this->belongsToMany(
            SuperAdminPermission::class,
            'super_admin_role_permissions',
            'role_id',
            'permission_id'
        );
    }

    public function users()
    {
        return $this->hasMany(SuperAdmin::class, 'role_id');
    }
}
