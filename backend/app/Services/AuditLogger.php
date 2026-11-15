<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public static function log(
        string $action,
        ?string $entityType = null,
        int|string|null $entityId = null,
        ?string $summary = null,
        array $changes = [],
        ?Request $request = null
    ): AuditLog {
        return AuditLog::create([
            'super_admin_id' => Auth::guard('super_admin')->id(),
            'entity_type' => $entityType,
            'entity_id' => $entityId ? (int) $entityId : null,
            'action' => $action,
            'summary' => $summary,
            'changes' => $changes ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'performed_at' => now(),
        ]);
    }
}
