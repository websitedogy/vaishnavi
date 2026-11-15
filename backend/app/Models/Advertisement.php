<?php

namespace App\Models;

use App\Enums\AdPlacement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Advertisement extends Model
{
    protected $fillable = [
        'tenant_id',
        'placement',
        'name',
        'advertiser',
        'content',
        'image_url',
        'link_url',
        'is_active',
        'impressions',
        'clicks',
        'revenue',
        'starts_at',
        'ends_at',
        'ad_type',
        'video_url',
        'adsense_slot',
    ];

    protected $casts = [
        'placement' => AdPlacement::class,
        'is_active' => 'boolean',
        'revenue' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function ctr(): float
    {
        return $this->impressions > 0
            ? round(($this->clicks / $this->impressions) * 100, 2)
            : 0;
    }
}
