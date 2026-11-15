<?php

namespace App\Modules\TenantAdmin\Services;

use App\Modules\SuperAdmin\Models\Tenant;
use App\Modules\SuperAdmin\Models\TenantBranding;
use Illuminate\Validation\ValidationException;

class BrandingService
{
    public function getBranding(int $tenantId): TenantBranding
    {
        $tenant = Tenant::findOrFail($tenantId);

        return TenantBranding::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'newspaper_name' => $tenant->name ?? 'Newspaper',
                'primary_color' => '#2563EB',
                'secondary_color' => '#0EA5E9',
                'typography' => 'Inter',
                'header_config' => [
                    'show_logo' => true,
                    'logo_position' => 'center',
                    'show_date' => true,
                    'show_search' => true,
                    'sticky' => true,
                ],
                'footer_config' => [
                    'show_footer' => true,
                    'copyright_text' => "© " . date('Y') . " {$tenant->name}. All rights reserved.",
                    'show_social_links' => true,
                ],
                'social_links' => [],
                'custom_css' => '',
                'is_published' => true,
                'version' => 1,
            ]
        );
    }

    public function updateBranding(int $tenantId, array $data, ?int $userId = null): TenantBranding
    {
        $branding = $this->getBranding($tenantId);

        $allowed = [
            'newspaper_name',
            'logo_url',
            'favicon_url',
            'primary_color',
            'secondary_color',
            'typography',
            'header_config',
            'footer_config',
            'social_links',
            'custom_css',
        ];

        $payload = array_intersect_key($data, array_flip($allowed));

        if (isset($payload['custom_css'])) {
            $payload['custom_css'] = $this->sanitizeCss($payload['custom_css']);
        }

        $branding->update(array_merge($payload, [
            'version' => ($branding->version ?? 1) + 1,
            'is_published' => true,
        ]));

        TenantAuditHelper::log($tenantId, $userId, 'branding_updated', 'branding', "Updated newspaper website branding theme (v{$branding->version}).");

        return $branding;
    }

    public function resetToDefaults(int $tenantId, ?int $userId = null): TenantBranding
    {
        $tenant = Tenant::findOrFail($tenantId);
        $branding = $this->getBranding($tenantId);

        $branding->update([
            'primary_color' => '#2563EB',
            'secondary_color' => '#0EA5E9',
            'typography' => 'Inter',
            'header_config' => [
                'show_logo' => true,
                'logo_position' => 'center',
                'show_date' => true,
                'show_search' => true,
                'sticky' => true,
            ],
            'footer_config' => [
                'show_footer' => true,
                'copyright_text' => "© " . date('Y') . " {$tenant->name}. All rights reserved.",
                'show_social_links' => true,
            ],
            'custom_css' => '',
            'version' => ($branding->version ?? 1) + 1,
        ]);

        TenantAuditHelper::log($tenantId, $userId, 'branding_reset', 'branding', "Reset branding configuration to system defaults.");

        return $branding;
    }

    public function sanitizeCss(string $css): string
    {
        // Strip script tags, javascript: pseudo-protocols, and remote dangerous imports
        $cleaned = preg_replace('/<\s*script[^>]*>.*?<\s*\/\s*script\s*>/is', '', $css);
        $cleaned = preg_replace('/javascript\s*:/i', '', $cleaned);
        $cleaned = preg_replace('/@import\s+[^;]+;/i', '', $cleaned);
        $cleaned = preg_replace('/expression\s*\([^)]*\)/i', '', $cleaned);
        $cleaned = preg_replace('/behavior\s*:[^;]+;/i', '', $cleaned);

        return trim($cleaned);
    }
}
