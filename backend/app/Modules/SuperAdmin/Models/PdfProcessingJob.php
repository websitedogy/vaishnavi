<?php

namespace App\Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Model;

class PdfProcessingJob extends Model
{
    protected $table = 'pdf_processing_jobs';

    protected $fillable = [
        'edition_id',
        'tenant_id',
        'job_uuid',
        'status',
        'current_page',
        'total_pages',
        'worker_id',
        'attempts',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function edition()
    {
        return $this->belongsTo(NewspaperEdition::class, 'edition_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
