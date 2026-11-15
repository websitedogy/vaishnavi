<?php

namespace App\Modules\PublicView\Services;

use App\Modules\SuperAdmin\Models\Tenant;
use App\Modules\SuperAdmin\Models\TenantDomain;
use App\Modules\SuperAdmin\Models\TenantBranding;
use App\Modules\TenantAdmin\Models\NewspaperProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicTenantService
{
    /**
     * Resolve tenant from request either via custom verified domain, subdomain, or route slug parameter.
     */
    public function resolveFromRequest(Request $request, ?string $slugFromRoute = null): ?Tenant
    {
        // 1. Direct route parameter (check slug, custom domain, or verified host)
        if (!empty($slugFromRoute)) {
            $identifier = strtolower(trim($slugFromRoute));
            $tenant = Tenant::where('slug', $identifier)->first();
            if ($tenant) {
                return $tenant;
            }

            $tenantByDomain = Tenant::where('domain', $identifier)->first();
            if ($tenantByDomain) {
                return $tenantByDomain;
            }

            $domainRecord = TenantDomain::where('host', $identifier)
                ->where('verification_status', 'verified')
                ->first();
            if ($domainRecord && $domainRecord->tenant) {
                return $domainRecord->tenant;
            }
        }

        // 2. Custom Domain or Subdomain from HTTP Host header
        $rawHost = $request->header('Host') ?: $request->getHost();
        $host = $this->normalizeHost($rawHost);

        if (!empty($host) && !in_array($host, ['localhost', '127.0.0.1', '::1'])) {
            // Check verified custom domain in tenant_domains table
            $domainRecord = TenantDomain::where('host', $host)
                ->where('verification_status', 'verified')
                ->first();

            if ($domainRecord && $domainRecord->tenant) {
                return $domainRecord->tenant;
            }

            // Check primary domain directly on tenants table
            $tenantByDomain = Tenant::where('domain', $host)->first();
            if ($tenantByDomain) {
                return $tenantByDomain;
            }

            // Check subdomain format: {tenantSlug}.platform-domain.com
            $parts = explode('.', $host);
            if (count($parts) >= 3) {
                $subdomain = $parts[0];
                $tenantBySubdomain = Tenant::where('slug', $subdomain)->first();
                if ($tenantBySubdomain) {
                    return $tenantBySubdomain;
                }
            }
        }

        return null;
    }

    /**
     * Normalize host string by lowercasing, stripping port, and eliminating control characters.
     */
    public function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));
        // Strip port if present (e.g. localhost:8000 -> localhost)
        if (strpos($host, ':') !== false) {
            $host = explode(':', $host)[0];
        }
        return preg_replace('/[^a-z0-9.-]/', '', $host);
    }

    /**
     * Get sanitized public branding details for tenant.
     */
    public function getPublicBranding(Tenant $tenant): array
    {
        $branding = TenantBranding::where('tenant_id', $tenant->id)->first();
        $profile = NewspaperProfile::where('tenant_id', $tenant->id)->first();

        return [
            'newspaper_name' => $this->sanitizeText($branding?->newspaper_name ?? $profile?->newspaper_name ?? $tenant->name),
            'logo_url' => $branding?->logo_url ?? $profile?->logo_url ?? $tenant->logo,
            'favicon_url' => $branding?->favicon_url ?? $profile?->favicon_url ?? '/favicon.ico',
            'primary_color' => $this->validateHexColor($branding?->primary_color, '#1E3A8A'),
            'secondary_color' => $this->validateHexColor($branding?->secondary_color, '#0284C7'),
            'typography' => $this->sanitizeFont($branding?->typography ?? 'Inter'),
            'header_config' => $branding?->header_config ?? [
                'style' => 'standard',
                'show_date' => true,
                'show_search' => true,
                'show_archive_btn' => true,
            ],
            'footer_config' => $branding?->footer_config ?? [
                'copyright' => "© " . date('Y') . " " . ($profile?->newspaper_name ?? $tenant->name) . ". All rights reserved.",
                'show_powered_by' => true,
            ],
            'social_links' => $this->sanitizeSocialLinks($branding?->social_links ?? []),
            'custom_css' => $this->sanitizeCss($branding?->custom_css ?? ''),
            'is_published' => (bool) ($branding?->is_published ?? true),
        ];
    }

    /**
     * Get public publication profile details.
     */
    public function getPublicProfile(Tenant $tenant): array
    {
        $profile = NewspaperProfile::where('tenant_id', $tenant->id)->first();

        return [
            'id' => $tenant->id,
            'slug' => $tenant->slug,
            'name' => $tenant->name,
            'newspaper_name' => $profile?->newspaper_name ?? $tenant->name,
            'company_name' => $profile?->company_name ?? $tenant->company_name,
            'tagline' => $profile?->about_newspaper ?? 'Digital Newspaper Edition',
            'about' => $profile?->about_newspaper ?? '',
            'publisher_name' => $profile?->publisher_name ?? $tenant->contact_person,
            'editor_name' => $profile?->editor_name ?? '',
            'contact_email' => $profile?->email ?? $tenant->email,
            'contact_phone' => $profile?->phone ?? $tenant->phone,
            'address' => $profile?->registered_address ?? $tenant->address,
            'state' => $tenant->state,
            'district' => $tenant->district,
            'default_language' => $tenant->default_language ?? 'te',
            'primary_domain' => $tenant->domain ?? ($tenant->slug . '.dogysaaspaltform.test'),
        ];
    }

    protected function sanitizeText(?string $val): string
    {
        return htmlspecialchars(strip_tags($val ?? ''), ENT_QUOTES, 'UTF-8');
    }

    protected function validateHexColor(?string $color, string $default): string
    {
        if ($color && preg_match('/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $color)) {
            return $color;
        }
        return $default;
    }

    protected function sanitizeFont(?string $font): string
    {
        $allowed = ['Inter', 'Roboto', 'Merriweather', 'Lora', 'Playfair Display', 'Open Sans', 'Poppins', 'Noto Sans Telugu'];
        return in_array($font, $allowed) ? $font : 'Inter';
    }

    protected function sanitizeSocialLinks(array $links): array
    {
        $clean = [];
        foreach ($links as $platform => $url) {
            if (is_string($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                $clean[$platform] = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
            }
        }
        return $clean;
    }

    protected function sanitizeCss(?string $css): string
    {
        if (empty($css)) {
            return '';
        }
        // Disallow dangerous expressions, script injections, and expression()
        $css = preg_replace('/javascript\s*:/i', '', $css);
        $css = preg_replace('/expression\s*\(/i', '', $css);
        $css = preg_replace('/behavior\s*:/i', '', $css);
        $css = strip_tags($css);
        return $css;
    }
}
