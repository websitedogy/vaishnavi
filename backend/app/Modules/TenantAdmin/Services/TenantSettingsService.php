<?php

namespace App\Modules\TenantAdmin\Services;

use App\Modules\SuperAdmin\Models\Tenant;
use Illuminate\Support\Facades\DB;

class TenantSettingsService
{
    public function getSettings(int $tenantId): array
    {
        $tenant = Tenant::findOrFail($tenantId);

        $customSettings = DB::table('tenant_configurations')
            ->where('tenant_id', $tenantId)
            ->pluck('value', 'key')
            ->toArray();

        return [
            'general' => [
                'language' => $customSettings['language'] ?? ($tenant->default_language ?? 'te'),
                'timezone' => $customSettings['timezone'] ?? ($tenant->timezone ?? 'Asia/Kolkata'),
                'date_format' => $customSettings['date_format'] ?? 'd-m-Y',
                'time_format' => $customSettings['time_format'] ?? '12h',
            ],
            'publishing' => [
                'default_publication_time' => $customSettings['default_publication_time'] ?? '05:00',
                'require_editor_approval' => ($customSettings['require_editor_approval'] ?? 'true') === 'true',
                'auto_archive_days' => (int)($customSettings['auto_archive_days'] ?? 90),
            ],
            'storage' => [
                'max_pdf_upload_mb' => 250,
                'allowed_extensions' => ['pdf', 'png', 'jpg', 'jpeg', 'webp'],
            ],
        ];
    }

    public function updateSettings(int $tenantId, array $settings, ?int $userId = null): array
    {
        foreach ($settings as $key => $value) {
            $valStr = is_bool($value) ? ($value ? 'true' : 'false') : (string)$value;
            DB::table('tenant_configurations')->updateOrInsert(
                ['tenant_id' => $tenantId, 'key' => $key],
                ['value' => $valStr, 'updated_at' => now()]
            );
        }

        TenantAuditHelper::log($tenantId, $userId, 'settings_updated', 'settings', "Updated tenant publishing settings.");

        return $this->getSettings($tenantId);
    }
}
