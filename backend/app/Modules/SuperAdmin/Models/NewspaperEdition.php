<?php

namespace App\Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Model;

class NewspaperEdition extends Model
{
    protected $table = 'newspaper_editions';

    protected $fillable = [
        'tenant_id',
        'edition_code',
        'edition_name',
        'publish_date',
        'total_pages',
        'file_size_mb',
        'pdf_s3_path',
        'status',
    ];

    protected $casts = [
        'publish_date' => 'date',
        'file_size_mb' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function jobs()
    {
        return $this->hasMany(PdfProcessingJob::class, 'edition_id');
    }
}
