<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedIp extends Model
{
    protected $fillable = [
        'ip_address',
        'reason',
        'is_active',
        'blocked_until',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'blocked_until' => 'datetime',
    ];
}
