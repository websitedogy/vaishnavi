<?php

namespace App\Modules\PublicView\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PublicView\Services\PublicCacheService;
use App\Modules\PublicView\Services\PublicEditionService;
use App\Modules\PublicView\Services\PublicManifestService;
use App\Modules\SuperAdmin\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EditionController extends Controller
{
    protected PublicEditionService $editionService;
    protected PublicManifestService $manifestService;
    protected PublicCacheService $cacheService;

    public function __construct(
        PublicEditionService $editionService,
        PublicManifestService $manifestService,
        PublicCacheService $cacheService
    ) {
        $this->editionService = $editionService;
        $this->manifestService = $manifestService;
        $this->cacheService = $cacheService;
    }

    /**
     * Get paginated list of published editions.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('public_tenant');

        $filters = $request->only(['category', 'from_date', 'to_date', 'language']);
        $perPage = min(max((int) $request->query('per_page', 12), 1), 50);

        $paginator = $this->editionService->paginate($tenant, $filters, $perPage);

        $items = collect($paginator->items())->map(function ($ed) {
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

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ], 200);
    }

    /**
     * Get the latest published edition manifest.
     */
    public function latest(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('public_tenant');

        $edition = $this->editionService->getLatest($tenant);

        if (!$edition) {
            return response()->json([
                'success' => false,
                'message' => 'No published editions found for this newspaper.',
            ], 404);
        }

        $etag = $this->manifestService->computeETag($edition);

        $notModified = $this->cacheService->handleConditionalEtag($request, $etag);
        if ($notModified) {
            return $notModified;
        }

        $manifest = $this->manifestService->getManifest($edition, $tenant);

        return $this->cacheService->withCacheHeaders(
            response()->json([
                'success' => true,
                'data' => $manifest,
            ], 200),
            $etag
        );
    }

    /**
     * Get a specific published edition manifest by date (YYYY-MM-DD) or edition code.
     */
    public function show(Request $request, string $tenantSlug, string $dateOrCode): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('public_tenant');

        $edition = $this->editionService->getByDateOrCode($tenant, $dateOrCode);

        if (!$edition) {
            return response()->json([
                'success' => false,
                'message' => 'Edition not found or has not been published.',
            ], 404);
        }

        $etag = $this->manifestService->computeETag($edition);

        $notModified = $this->cacheService->handleConditionalEtag($request, $etag);
        if ($notModified) {
            return $notModified;
        }

        $manifest = $this->manifestService->getManifest($edition, $tenant);

        return $this->cacheService->withCacheHeaders(
            response()->json([
                'success' => true,
                'data' => $manifest,
            ], 200),
            $etag
        );
    }
}
