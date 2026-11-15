<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupRecord extends Model
{
    protected $fillable = [
        'type',
        'file_path',
        'file_size',
        'status',
        'backup_taken_at',
        'restored_at',
        'notes',
    ];

    protected $casts = [
        'backup_taken_at' => 'datetime',
        'restored_at' => 'datetime',
    ];
}
