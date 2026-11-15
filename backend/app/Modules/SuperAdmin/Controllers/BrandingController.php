<?php

namespace App\Modules\SuperAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SuperAdmin\Models\PlatformBranding;
use App\Modules\SuperAdmin\Models\TenantBranding;
use App\Modules\SuperAdmin\Models\BrandingVersion;
use App\Modules\SuperAdmin\Services\BrandingEngineService;
use App\Shared\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class BrandingController extends Controller
{
    use ApiResponseTrait;

    protected BrandingEngineService $brandingService;

    public function __construct(BrandingEngineService $brandingService)
    {
        $this->brandingService = $brandingService;
    }

    public function getPlatformBranding()
    {
        $branding = PlatformBranding::firstOrCreate(['id' => 1], [
            'platform_name' => 'DOGY ePAPER SaaS',
            'primary_color' => '#2563EB',
            'secondary_color' => '#0EA5E9',
        ]);

        return $this->successResponse($branding);
    }

    public function updatePlatformBranding(Request $request)
    {
        $validated = $request->validate([
            'platform_name' => 'sometimes|required|string|max:255',
            'tag_line' => 'nullable|string|max:255',
            'logo_url' => 'nullable|string|max:500',
            'favicon_url' => 'nullable|string|max:500',
            'primary_color' => 'nullable|string|max:20',
            'secondary_color' => 'nullable|string|max:20',
            'header_config' => 'nullable|array',
            'footer_config' => 'nullable|array',
            'custom_css' => 'nullable|string',
        ]);

        $adminId = $request->user() ? $request->user()->id : null;
        $branding = $this->brandingService->updatePlatformBranding($validated, $adminId);

        return $this->successResponse($branding, 'Platform branding updated.');
    }

    public function getTenantBranding(int $tenantId)
    {
        $branding = TenantBranding::where('tenant_id', $tenantId)->first();
        $versions = BrandingVersion::where('tenant_id', $tenantId)->orderByDesc('version_number')->limit(10)->get();

        return $this->successResponse([
            'branding' => $branding,
            'versions' => $versions,
        ]);
    }

    public function updateTenantBranding(Request $request, int $tenantId)
    {
        $validated = $request->validate([
            'newspaper_name' => 'sometimes|required|string|max:255',
            'logo_url' => 'nullable|string|max:500',
            'favicon_url' => 'nullable|string|max:500',
            'primary_color' => 'nullable|string|max:20',
            'secondary_color' => 'nullable|string|max:20',
            'typography' => 'nullable|string|max:100',
            'header_config' => 'nullable|array',
            'footer_config' => 'nullable|array',
            'social_links' => 'nullable|array',
            'custom_css' => 'nullable|string',
            'change_summary' => 'nullable|string|max:255',
        ]);

        $adminId = $request->user() ? $request->user()->id : null;
        $branding = $this->brandingService->updateTenantBranding($tenantId, $validated, $adminId);

        return $this->successResponse($branding, 'Tenant branding updated.');
    }

    public function rollbackTenantBranding(Request $request, int $tenantId, int $versionNumber)
    {
        $adminId = $request->user() ? $request->user()->id : null;
        $branding = $this->brandingService->rollbackTenantBranding($tenantId, $versionNumber, $adminId);

        return $this->successResponse($branding, "Branding successfully restored to v{$versionNumber}.");
    }
}
