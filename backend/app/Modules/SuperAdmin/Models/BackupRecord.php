<?php

namespace App\Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Model;

class BackupRecord extends Model
{
    protected $table = 'backup_records';

    protected $fillable = [
        'type',
        'file_path',
        'file_size',
        'status',
        'backup_taken_at',
        'restored_at',
        'notes',
        'backup_type',
        'aws_backup_arn',
        'encryption_status',
        'retention_days',
        'rpo_minutes',
        'rto_minutes',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'backup_taken_at' => 'datetime',
        'restored_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
