<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'super_admin_id',
        'entity_type',
        'entity_id',
        'action',
        'summary',
        'changes',
        'ip_address',
        'user_agent',
        'performed_at',
    ];

    protected $casts = [
        'changes' => 'array',
        'performed_at' => 'datetime',
    ];

    public function superAdmin(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class);
    }
}
