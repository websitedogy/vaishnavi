<?php

namespace App\Modules\TenantAdmin\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\SuperAdmin\Models\Tenant;
use App\Models\User;

class Edition extends Model
{
    protected $table = 'newspaper_editions';

    protected $fillable = [
        'tenant_id',
        'edition_code',
        'edition_name',
        'edition_title',
        'edition_language',
        'category',
        'description',
        'publish_date',
        'total_pages',
        'file_size_mb',
        'thumbnail_path',
        'original_pdf_path',
        'pdf_s3_path',
        'checksum_sha256',
        'status', // draft, uploaded, processing, processing_failed, ready_for_review, scheduled, published, archived
        'public_manifest',
        'manifest_generated_at',
        'scheduled_at',
        'published_at',
        'archived_at',
        'created_by_user_id',
    ];

    protected $casts = [
        'publish_date' => 'date:Y-m-d',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'archived_at' => 'datetime',
        'manifest_generated_at' => 'datetime',
        'public_manifest' => 'array',
        'total_pages' => 'integer',
        'file_size_mb' => 'float',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function pages()
    {
        return $this->hasMany(EditionPage::class, 'edition_id')->orderBy('sort_order', 'asc');
    }

    public function uploads()
    {
        return $this->hasMany(PdfUpload::class, 'edition_id');
    }

    public function schedule()
    {
        return $this->hasOne(PublishingSchedule::class, 'edition_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
