<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reader extends Model
{
    protected $fillable = ['tenant_id', 'name', 'email', 'password', 'is_premium', 'is_banned', 'last_seen_at'];

    protected $casts = [
        'is_premium' => 'boolean',
        'is_banned' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    protected $hidden = ['password'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
