<?php

namespace App\Modules\TenantAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TenantAdmin\Services\BrandingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BrandingController extends Controller
{
    protected BrandingService $brandingService;

    public function __construct(BrandingService $brandingService)
    {
        $this->brandingService = $brandingService;
    }

    public function show(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $branding = $this->brandingService->getBranding($tenantId);

        return response()->json([
            'success' => true,
            'data' => $branding,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $validated = $request->validate([
            'newspaper_name' => 'sometimes|string|max:255',
            'logo_url' => 'nullable|string',
            'favicon_url' => 'nullable|string',
            'primary_color' => 'sometimes|string|max:20',
            'secondary_color' => 'sometimes|string|max:20',
            'typography' => 'sometimes|string|max:50',
            'header_config' => 'nullable|array',
            'footer_config' => 'nullable|array',
            'social_links' => 'nullable|array',
            'custom_css' => 'nullable|string',
        ]);

        $branding = $this->brandingService->updateBranding($tenantId, $validated, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Branding configuration saved.',
            'data' => $branding,
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $branding = $this->brandingService->resetToDefaults($tenantId, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Branding reset to system default palette.',
            'data' => $branding,
        ]);
    }
}
