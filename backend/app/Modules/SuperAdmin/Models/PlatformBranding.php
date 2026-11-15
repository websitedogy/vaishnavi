<?php

namespace App\Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformBranding extends Model
{
    protected $table = 'platform_branding';

    protected $fillable = [
        'platform_name',
        'tag_line',
        'logo_url',
        'favicon_url',
        'primary_color',
        'secondary_color',
        'header_config',
        'footer_config',
        'custom_css',
    ];

    protected $casts = [
        'header_config' => 'array',
        'footer_config' => 'array',
    ];
}
