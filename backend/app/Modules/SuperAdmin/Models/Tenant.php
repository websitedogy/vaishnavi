<?php

namespace App\Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $table = 'tenants';

    protected $fillable = [
        'name',
        'company_name',
        'contact_person',
        'slug',
        'email',
        'phone',
        'domain',
        'address',
        'state',
        'district',
        'mandal',
        'logo',
        'subscription',
        'package_id',
        'subscription_amount',
        'billing_cycle',
        'storage_limit_mb',
        'storage_used_mb',
        'default_language',
        'timezone',
        'database_name',
        'storage_bucket',
        'storage_prefix',
        'status',
        'trial_ends_at',
        'subscription_ends_at',
        'suspended_at',
    ];

    protected $casts = [
        'subscription_amount' => 'decimal:2',
        'storage_used_mb' => 'decimal:2',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'suspended_at' => 'datetime',
    ];

    public function domains()
    {
        return $this->hasMany(TenantDomain::class, 'tenant_id');
    }

    public function primaryDomain()
    {
        return $this->hasOne(TenantDomain::class, 'tenant_id')->where('is_primary', true);
    }

    public function contacts()
    {
        return $this->hasMany(TenantContact::class, 'tenant_id');
    }

    public function configurations()
    {
        return $this->hasMany(TenantConfiguration::class, 'tenant_id');
    }

    public function invitations()
    {
        return $this->hasMany(TenantAdminInvitation::class, 'tenant_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(TenantSubscription::class, 'tenant_id');
    }

    public function activeSubscription()
    {
        return $this->hasOne(TenantSubscription::class, 'tenant_id')->latestOfMany();
    }

    public function package()
    {
        return $this->belongsTo(SubscriptionPackage::class, 'package_id');
    }

    public function branding()
    {
        return $this->hasOne(TenantBranding::class, 'tenant_id');
    }

    public function invoices()
    {
        return $this->hasMany(SubscriptionInvoice::class, 'tenant_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'tenant_id');
    }

    public function editions()
    {
        return $this->hasMany(NewspaperEdition::class, 'tenant_id');
    }
}
