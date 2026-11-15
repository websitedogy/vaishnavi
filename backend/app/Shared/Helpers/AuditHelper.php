<?php

namespace App\Shared\Helpers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuditHelper
{
    public static function log(
        ?int $superAdminId,
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $summary = null,
        array|object|null $changes = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        try {
            DB::table('audit_logs')->insert([
                'super_admin_id' => $superAdminId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'action' => strtoupper($action),
                'summary' => $summary,
                'changes' => $changes ? json_encode($changes) : null,
                'ip_address' => $ipAddress ?? request()->ip(),
                'user_agent' => $userAgent ?? substr(request()->userAgent() ?? '', 0, 500),
                'performed_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Audit log failed: ' . $e->getMessage());
        }
    }
}
