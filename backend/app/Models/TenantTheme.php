<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantTheme extends Model
{
    protected $fillable = [
        'tenant_id',
        'theme_preset',
        'layout',
        'font_family',
        'primary_color',
        'secondary_color',
        'accent_color',
        'features',
    ];

    protected $casts = [
        'features' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function defaultFeatures(): array
    {
        return [
            'live_tv' => false,
            'video_section' => true,
            'notifications' => true,
            'polls' => false,
        ];
    }

    public function featureEnabled(string $key): bool
    {
        $features = $this->features ?? self::defaultFeatures();

        return (bool) ($features[$key] ?? false);
    }
}
