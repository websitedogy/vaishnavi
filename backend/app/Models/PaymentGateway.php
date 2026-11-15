<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    protected $fillable = [
        'name',
        'is_enabled',
        'mode',
        'merchant_key',
        'secret_key',
        'webhook_secret',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];
}
