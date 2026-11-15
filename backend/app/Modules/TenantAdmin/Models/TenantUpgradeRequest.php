<?php

namespace App\Modules\TenantAdmin\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\SuperAdmin\Models\Tenant;
use App\Modules\SuperAdmin\Models\SubscriptionPackage;
use App\Models\User;

class TenantUpgradeRequest extends Model
{
    protected $table = 'tenant_upgrade_requests';

    protected $fillable = [
        'tenant_id',
        'requested_by_user_id',
        'target_package_id',
        'notes',
        'status', // pending, approved, rejected
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function targetPackage()
    {
        return $this->belongsTo(SubscriptionPackage::class, 'target_package_id');
    }
}
