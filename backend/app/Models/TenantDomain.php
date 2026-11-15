<?php

namespace App\Models;

use App\Enums\SslStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantDomain extends Model
{
    protected $fillable = [
        'tenant_id',
        'type',
        'host',
        'is_primary',
        'ssl_status',
        'ssl_auto',
        'ssl_issued_at',
        'ssl_expires_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'ssl_auto' => 'boolean',
        'ssl_status' => SslStatus::class,
        'ssl_issued_at' => 'datetime',
        'ssl_expires_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function fullUrl(): string
    {
        return 'https://'.$this->host;
    }

    public function provisionSsl(): void
    {
        if (! $this->ssl_auto) {
            return;
        }

        $this->update([
            'ssl_status' => SslStatus::Provisioning,
        ]);

        $this->update([
            'ssl_status' => SslStatus::Active,
            'ssl_issued_at' => now(),
            'ssl_expires_at' => now()->addMonths(3),
        ]);
    }
}
