<?php

namespace App\Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Model;

class InfrastructureService extends Model
{
    protected $table = 'infrastructure_services';

    protected $fillable = [
        'service_name',
        'service_type',
        'aws_region',
        'resource_arn',
        'status',
        'cpu_percent',
        'memory_percent',
        'latency_ms',
        'last_checked_at',
    ];

    protected $casts = [
        'cpu_percent' => 'decimal:2',
        'memory_percent' => 'decimal:2',
        'last_checked_at' => 'datetime',
    ];

    public function alerts()
    {
        return $this->hasMany(InfrastructureAlert::class, 'service_id');
    }
}
