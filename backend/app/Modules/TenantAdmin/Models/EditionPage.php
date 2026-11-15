<?php

namespace App\Modules\TenantAdmin\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\SuperAdmin\Models\Tenant;

class EditionPage extends Model
{
    protected $table = 'edition_pages';

    protected $fillable = [
        'edition_id',
        'tenant_id',
        'page_number',
        'page_title',
        'image_path',
        'thumbnail_path',
        'width',
        'height',
        'rotation',
        'is_visible',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'page_number' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'rotation' => 'integer',
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    public function edition()
    {
        return $this->belongsTo(Edition::class, 'edition_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
