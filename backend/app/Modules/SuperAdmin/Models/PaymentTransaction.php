<?php

namespace App\Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $table = 'payment_transactions';

    protected $fillable = [
        'payment_id',
        'gateway_transaction_id',
        'gateway_order_id',
        'payload',
        'response',
        'status',
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
}
