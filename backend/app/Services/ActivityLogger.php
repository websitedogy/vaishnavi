<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public static function log(string $type, string $title, ?string $description = null, ?Tenant $tenant = null, array $metadata = []): ActivityLog
    {
        return ActivityLog::create([
            'super_admin_id' => Auth::guard('super_admin')->id(),
            'tenant_id' => $tenant?->id,
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'metadata' => $metadata ?: null,
        ]);
    }
}
