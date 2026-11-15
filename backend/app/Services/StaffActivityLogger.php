<?php

namespace App\Services;

use App\Models\StaffActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class StaffActivityLogger
{
    public static function log(string $action, string $description, ?Model $subject = null): void
    {
        $user = Auth::guard('tenant_admin')->user();
        if (! $user?->tenant_id) {
            return;
        }

        StaffActivityLog::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->id,
        ]);
    }
}
