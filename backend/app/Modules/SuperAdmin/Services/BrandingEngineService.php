<?php

namespace App\Modules\SuperAdmin\Services;

use App\Modules\SuperAdmin\Models\PlatformBranding;
use App\Modules\SuperAdmin\Models\TenantBranding;
use App\Modules\SuperAdmin\Models\BrandingVersion;
use App\Shared\Helpers\AuditHelper;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BrandingEngineService
{
    public function updatePlatformBranding(array $data, ?int $adminId = null): PlatformBranding
    {
        $branding = PlatformBranding::firstOrCreate(['id' => 1]);

        if (isset($data['custom_css'])) {
            $data['custom_css'] = $this->sanitizeCustomCss($data['custom_css']);
        }

        $branding->update($data);

        AuditHelper::log($adminId, 'PLATFORM_BRANDING_UPDATED', 'PlatformBranding', $branding->id, 'Global platform branding updated.');

        return $branding;
    }

    public function updateTenantBranding(int $tenantId, array $data, ?int $adminId = null): TenantBranding
    {
        return DB::transaction(function () use ($tenantId, $data, $adminId) {
            $branding = TenantBranding::firstOrCreate(['tenant_id' => $tenantId], [
                'newspaper_name' => $data['newspaper_name'] ?? 'ePaper Publisher',
            ]);

            if (isset($data['custom_css'])) {
                $data['custom_css'] = $this->sanitizeCustomCss($data['custom_css']);
            }

            $currentVersion = $branding->version ?? 1;
            $newVersion = $currentVersion + 1;
            $data['version'] = $newVersion;

            $branding->update($data);

            // Create immutable version snapshot
            BrandingVersion::create([
                'tenant_id' => $tenantId,
                'version_number' => $newVersion,
                'configuration' => $branding->toArray(),
                'published_at' => Carbon::now(),
                'published_by_admin_id' => $adminId,
                'change_summary' => $data['change_summary'] ?? "Updated branding configuration to v{$newVersion}.",
            ]);

            AuditHelper::log($adminId, 'TENANT_BRANDING_UPDATED', 'TenantBranding', $branding->id, "Tenant branding updated to v{$newVersion}.");

            return $branding;
        });
    }

    public function rollbackTenantBranding(int $tenantId, int $versionNumber, ?int $adminId = null): TenantBranding
    {
        return DB::transaction(function () use ($tenantId, $versionNumber, $adminId) {
            $version = BrandingVersion::where('tenant_id', $tenantId)
                ->where('version_number', $versionNumber)
                ->firstOrFail();

            $branding = TenantBranding::where('tenant_id', $tenantId)->firstOrFail();
            $config = $version->configuration;

            // Retain IDs and timestamps
            unset($config['id'], $config['created_at'], $config['updated_at']);
            $config['version'] = $branding->version + 1;

            $branding->update($config);

            AuditHelper::log($adminId, 'TENANT_BRANDING_ROLLBACK', 'TenantBranding', $branding->id, "Tenant branding rolled back to snapshot v{$versionNumber}.");

            return $branding;
        });
    }

    /**
     * Sanitizes custom CSS to prevent XSS, script injection, and unsafe imports.
     */
    public function sanitizeCustomCss(?string $css): string
    {
        if (empty($css)) {
            return '';
        }

        // Strip HTML tags
        $css = strip_tags($css);

        // Strip dangerous CSS constructs
        $patterns = [
            '/@import[^;]*;/i',                // Remove external imports
            '/expression\s*\(.*?\)/i',          // Remove IE expressions
            '/javascript\s*:/i',                // Remove javascript: pseudo-protocol
            '/behavior\s*:/i',                  // Remove HTC behaviors
            '/-moz-binding\s*:/i',              // Remove XBL bindings
            '/url\s*\(\s*["\']?\s*data\s*:[^)]*\)/i', // Remove data: URIs
        ];

        return preg_replace($patterns, '', $css);
    }
}
