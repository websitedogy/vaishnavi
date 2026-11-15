<?php

namespace App\Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Model;

class InfrastructureAlert extends Model
{
    protected $table = 'infrastructure_alerts';

    protected $fillable = [
        'service_id',
        'severity',
        'title',
        'description',
        'status',
        'triggered_at',
        'resolved_at',
        'resolved_by_admin_id',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function service()
    {
        return $this->belongsTo(InfrastructureService::class, 'service_id');
    }
}
