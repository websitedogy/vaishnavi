<?php

namespace App\Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Model;

class PackageEntitlement extends Model
{
    protected $table = 'package_entitlements';

    protected $fillable = [
        'package_id',
        'max_domains',
        'storage_limit_gb',
        'pdf_upload_limit_mb',
        'monthly_edition_limit',
        'admin_user_limit',
        'custom_branding_enabled',
        'priority_support_enabled',
        'analytics_enabled',
        'custom_entitlements',
    ];

    protected $casts = [
        'custom_branding_enabled' => 'boolean',
        'priority_support_enabled' => 'boolean',
        'analytics_enabled' => 'boolean',
        'custom_entitlements' => 'array',
    ];

    public function package()
    {
        return $this->belongsTo(SubscriptionPackage::class, 'package_id');
    }
}
