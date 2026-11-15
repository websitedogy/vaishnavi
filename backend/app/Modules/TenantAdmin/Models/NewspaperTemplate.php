<?php

namespace App\Modules\TenantAdmin\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\SuperAdmin\Models\Tenant;

class NewspaperTemplate extends Model
{
    protected $table = 'newspaper_templates';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'thumbnail_url',
        'version',
        'layout_config',
        'color_palette',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'layout_config' => 'array',
        'color_palette' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
