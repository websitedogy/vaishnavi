<?php

namespace App\Modules\TenantAdmin\Services;

use App\Modules\TenantAdmin\Models\TenantActivityLog;
use Carbon\Carbon;

class TenantAuditHelper
{
    public static function log(int $tenantId, ?int $userId, string $action, string $module, string $description): void
    {
        try {
            TenantActivityLog::create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'action' => $action,
                'module' => $module,
                'description' => $description,
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'user_agent' => substr(request()->userAgent() ?? 'CLI/System', 0, 255),
                'created_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            // Non-blocking log failure
        }
    }
}
