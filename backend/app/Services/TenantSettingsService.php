<?php

namespace App\Services;

use App\Models\TenantSetting;

class TenantSettingsService
{
    public function get(int $tenantId, string $group): array
    {
        $defaults = config("tenant-modules.{$group}", []);
        $row = TenantSetting::where('tenant_id', $tenantId)->where('group', $group)->first();

        return array_merge($defaults, $row?->payload ?? []);
    }

    public function save(int $tenantId, string $group, array $payload): void
    {
        $defaults = config("tenant-modules.{$group}", []);
        $merged = array_merge($defaults, $payload);

        TenantSetting::updateOrCreate(
            ['tenant_id' => $tenantId, 'group' => $group],
            ['payload' => $merged]
        );
    }
}
