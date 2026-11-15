<?php

namespace App\Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Model;

class StorageUsageRecord extends Model
{
    protected $table = 'storage_usage_records';

    protected $fillable = [
        'tenant_id',
        'record_date',
        'pdf_storage_mb',
        'page_images_storage_mb',
        'thumbnails_storage_mb',
        'total_storage_mb',
        'file_count',
    ];

    protected $casts = [
        'record_date' => 'date',
        'pdf_storage_mb' => 'decimal:2',
        'page_images_storage_mb' => 'decimal:2',
        'thumbnails_storage_mb' => 'decimal:2',
        'total_storage_mb' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
