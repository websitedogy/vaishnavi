<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantTrafficStat extends Model
{
    protected $fillable = [
        'tenant_id',
        'date',
        'visitors',
        'page_views',
        'top_news_views',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
