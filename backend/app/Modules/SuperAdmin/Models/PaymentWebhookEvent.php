<?php

namespace App\Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentWebhookEvent extends Model
{
    protected $table = 'payment_webhook_events';

    protected $fillable = [
        'gateway',
        'event_type',
        'event_id',
        'payload',
        'is_processed',
        'processed_at',
        'signature_verified',
        'error',
    ];

    protected $casts = [
        'payload' => 'array',
        'is_processed' => 'boolean',
        'signature_verified' => 'boolean',
        'processed_at' => 'datetime',
    ];
}
