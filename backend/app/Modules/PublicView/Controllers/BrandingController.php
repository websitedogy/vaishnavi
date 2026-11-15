<?php

namespace App\Modules\PublicView\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PublicView\Services\PublicCacheService;
use App\Modules\PublicView\Services\PublicTenantService;
use App\Modules\SuperAdmin\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandingController extends Controller
{
    protected PublicTenantService $tenantService;
    protected PublicCacheService $cacheService;

    public function __construct(
        PublicTenantService $tenantService,
        PublicCacheService $cacheService
    ) {
        $this->tenantService = $tenantService;
        $this->cacheService = $cacheService;
    }

    /**
     * Get public branding and publication profile for tenant.
     */
    public function show(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('public_tenant');

        $branding = $this->tenantService->getPublicBranding($tenant);
        $profile = $this->tenantService->getPublicProfile($tenant);

        $etag = '"' . md5($tenant->id . '_' . ($branding['newspaper_name'] ?? '') . '_' . ($branding['primary_color'] ?? '')) . '"';

        $notModified = $this->cacheService->handleConditionalEtag($request, $etag, 300, 600);
        if ($notModified) {
            return $notModified;
        }

        return $this->cacheService->withCacheHeaders(
            response()->json([
                'success' => true,
                'data' => [
                    'publication' => $profile,
                    'branding' => $branding,
                ],
            ], 200),
            $etag,
            300,
            600
        );
    }
}
