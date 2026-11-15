<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalNotification extends Model
{
    protected $fillable = [
        'type',
        'title',
        'message',
        'channels',
        'is_sent',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'channels' => 'array',
        'is_sent' => 'boolean',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];
}
