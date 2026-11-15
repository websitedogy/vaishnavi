<?php

namespace App\Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookDeliveryLog extends Model
{
    protected $table = 'webhook_delivery_logs';

    protected $fillable = [
        'endpoint_id',
        'event',
        'payload',
        'response_code',
        'response_body',
        'status',
        'attempt',
        'attempted_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempted_at' => 'datetime',
    ];

    public function endpoint()
    {
        return $this->belongsTo(WebhookEndpoint::class, 'endpoint_id');
    }
}
