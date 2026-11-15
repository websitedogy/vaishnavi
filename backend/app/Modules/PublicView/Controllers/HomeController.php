<?php

namespace App\Modules\PublicView\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PublicView\Services\PublicCacheService;
use App\Modules\PublicView\Services\PublicEditionService;
use App\Modules\PublicView\Services\PublicManifestService;
use App\Modules\PublicView\Services\PublicSeoService;
use App\Modules\PublicView\Services\PublicTenantService;
use App\Modules\SuperAdmin\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    protected PublicTenantService $tenantService;
    protected PublicEditionService $editionService;
    protected PublicManifestService $manifestService;
    protected PublicCacheService $cacheService;
    protected PublicSeoService $seoService;

    public function __construct(
        PublicTenantService $tenantService,
        PublicEditionService $editionService,
        PublicManifestService $manifestService,
        PublicCacheService $cacheService,
        PublicSeoService $seoService
    ) {
        $this->tenantService = $tenantService;
        $this->editionService = $editionService;
        $this->manifestService = $manifestService;
        $this->cacheService = $cacheService;
        $this->seoService = $seoService;
    }

    /**
     * Get aggregated public homepage data for tenant newspaper.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('public_tenant');

        $profile = $this->tenantService->getPublicProfile($tenant);
        $branding = $this->tenantService->getPublicBranding($tenant);
        $latestEdition = $this->editionService->getLatest($tenant);
        $recentEditions = $this->editionService->getRecentEditions($tenant, 6);
        $categories = $this->editionService->getCategories($tenant);
        $seo = $this->seoService->getSeoMetadata($tenant, $latestEdition, $request->root());

        $latestManifest = null;
        if ($latestEdition) {
            $latestManifest = $this->manifestService->getManifest($latestEdition, $tenant);
        }

        // Format recent editions for grid
        $formattedRecents = $recentEditions->map(function ($ed) {
            return [
                'id' => $ed->id,
                'edition_code' => $ed->edition_code,
                'edition_title' => $ed->edition_title ?? $ed->edition_name,
                'edition_name' => $ed->edition_name,
                'category' => $ed->category ?? 'Main Edition',
                'publish_date' => $ed->publish_date ? $ed->publish_date->format('Y-m-d') : null,
                'total_pages' => $ed->total_pages,
                'thumbnail_url' => $this->manifestService->sanitizePublicUrl($ed->thumbnail_path),
            ];
        });

        $etag = '"' . md5($tenant->id . '_' . ($latestEdition ? $latestEdition->edition_code . '_' . $latestEdition->checksum_sha256 : 'no_editions')) . '"';

        $notModified = $this->cacheService->handleConditionalEtag($request, $etag, 60, 300);
        if ($notModified) {
            return $notModified;
        }

        $payload = [
            'success' => true,
            'data' => [
                'publication' => $profile,
                'branding' => $branding,
                'has_editions' => $latestEdition !== null,
                'latest_edition' => $latestManifest,
                'recent_editions' => $formattedRecents,
                'categories' => $categories,
                'seo' => $seo,
            ],
        ];

        return $this->cacheService->withCacheHeaders(response()->json($payload, 200), $etag, 60, 300);
    }
}
