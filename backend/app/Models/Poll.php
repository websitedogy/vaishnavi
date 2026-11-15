<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Poll extends Model
{
    protected $fillable = ['tenant_id', 'question', 'options', 'votes', 'is_active', 'closes_at'];

    protected $casts = [
        'options' => 'array',
        'votes' => 'array',
        'is_active' => 'boolean',
        'closes_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function totalVotes(): int
    {
        return array_sum($this->votes ?? []);
    }
}
