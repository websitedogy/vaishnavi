<?php

namespace App\Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Model;

class DeploymentLog extends Model
{
    protected $table = 'deployment_logs';

    protected $fillable = [
        'service_name',
        'version_tag',
        'commit_hash',
        'status',
        'triggered_by',
        'summary',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
