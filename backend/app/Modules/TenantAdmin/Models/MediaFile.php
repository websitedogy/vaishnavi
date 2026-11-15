<?php

namespace App\Modules\TenantAdmin\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\SuperAdmin\Models\Tenant;
use App\Models\User;

class MediaFile extends Model
{
    protected $table = 'media_files';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'path',
        'disk',
        'type',
        'mime_type',
        'size',
        'thumbnail_path',
        'alt_text',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
