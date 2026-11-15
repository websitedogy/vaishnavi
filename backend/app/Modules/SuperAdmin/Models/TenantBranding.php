<?php

namespace App\Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Model;

class TenantBranding extends Model
{
    protected $table = 'tenant_branding';

    protected $fillable = [
        'tenant_id',
        'newspaper_name',
        'logo_url',
        'favicon_url',
        'primary_color',
        'secondary_color',
        'typography',
        'nav_links',
        'header_config',
        'footer_config',
        'social_links',
        'custom_css',
        'is_published',
        'version',
    ];

    protected $casts = [
        'nav_links' => 'array',
        'header_config' => 'array',
        'footer_config' => 'array',
        'social_links' => 'array',
        'is_published' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
