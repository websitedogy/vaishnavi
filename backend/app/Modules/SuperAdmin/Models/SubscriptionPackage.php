<?php

namespace App\Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPackage extends Model
{
    protected $table = 'subscription_packages';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'billing_cycle',
        'price',
        'currency',
        'trial_days',
        'is_active',
        'is_popular',
        'is_custom',
        'version',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
        'is_custom' => 'boolean',
    ];

    public function entitlements()
    {
        return $this->hasOne(PackageEntitlement::class, 'package_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(TenantSubscription::class, 'package_id');
    }
}
