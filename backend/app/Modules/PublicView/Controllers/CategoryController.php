<?php

namespace App\Modules\PublicView\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PublicView\Services\PublicEditionService;
use App\Modules\PublicView\Services\PublicManifestService;
use App\Modules\SuperAdmin\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected PublicEditionService $editionService;
    protected PublicManifestService $manifestService;

    public function __construct(
        PublicEditionService $editionService,
        PublicManifestService $manifestService
    ) {
        $this->editionService = $editionService;
        $this->manifestService = $manifestService;
    }

    /**
     * List distinct categories for published editions.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('public_tenant');

        $categories = $this->editionService->getCategories($tenant);

        return response()->json([
            'success' => true,
            'data' => $categories,
        ], 200, [
            'Cache-Control' => 'public, max-age=300, s-maxage=600',
        ]);
    }

    /**
     * Get published editions for a specific category.
     */
    public function show(Request $request, string $tenantSlug, string $category): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('public_tenant');

        $perPage = min(max((int) $request->query('per_page', 12), 1), 50);
        $paginator = $this->editionService->paginate($tenant, ['category' => $category], $perPage);

        $results = collect($paginator->items())->map(function ($ed) {
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
            'data' => [
                'category' => $category,
                'editions' => $results,
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ], 200);
    }
}
