<?php

namespace App\Modules\TenantAdmin\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\SuperAdmin\Models\Tenant;
use App\Models\User;

class PdfUpload extends Model
{
    protected $table = 'pdf_uploads';

    protected $fillable = [
        'tenant_id',
        'edition_id',
        'uploaded_by_user_id',
        'original_filename',
        'stored_filename',
        'file_path',
        'file_size',
        'mime_type',
        'page_count',
        'checksum_sha256',
        'status',
        'error_message',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'page_count' => 'integer',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function edition()
    {
        return $this->belongsTo(Edition::class, 'edition_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
