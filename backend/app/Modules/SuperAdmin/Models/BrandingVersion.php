<?php

namespace App\Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Model;

class BrandingVersion extends Model
{
    protected $table = 'branding_versions';

    protected $fillable = [
        'tenant_id',
        'version_number',
        'configuration',
        'published_at',
        'published_by_admin_id',
        'change_summary',
    ];

    protected $casts = [
        'configuration' => 'array',
        'published_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
