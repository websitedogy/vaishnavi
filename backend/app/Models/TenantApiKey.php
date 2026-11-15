<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantApiKey extends Model
{
    protected $fillable = ['tenant_id', 'name', 'key_prefix', 'key_hash', 'webhooks', 'is_active'];

    protected $casts = [
        'webhooks' => 'array',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
