<?php

namespace App\Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Model;

class ReportJob extends Model
{
    protected $table = 'report_jobs';

    protected $fillable = [
        'report_type',
        'format',
        'filters',
        'status',
        'file_path',
        'file_size_kb',
        'generated_by_admin_id',
        'error',
        'completed_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'file_size_kb' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function generatedBy()
    {
        return $this->belongsTo(SuperAdmin::class, 'generated_by_admin_id');
    }
}
