<?php

namespace App\Modules\TenantAdmin\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\SuperAdmin\Models\Tenant;
use App\Models\User;

class PublishingSchedule extends Model
{
    protected $table = 'publishing_schedules';

    protected $fillable = [
        'tenant_id',
        'edition_id',
        'scheduled_by_user_id',
        'scheduled_at',
        'timezone',
        'status',
        'published_at',
        'cancelled_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function edition()
    {
        return $this->belongsTo(Edition::class, 'edition_id');
    }

    public function scheduler()
    {
        return $this->belongsTo(User::class, 'scheduled_by_user_id');
    }
}
