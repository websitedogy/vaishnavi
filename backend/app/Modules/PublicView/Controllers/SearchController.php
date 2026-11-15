<?php

namespace App\Modules\PublicView\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PublicView\Services\PublicManifestService;
use App\Modules\PublicView\Services\PublicSearchService;
use App\Modules\SuperAdmin\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SearchController extends Controller
{
    protected PublicSearchService $searchService;
    protected PublicManifestService $manifestService;

    public function __construct(
        PublicSearchService $searchService,
        PublicManifestService $manifestService
    ) {
        $this->searchService = $searchService;
        $this->manifestService = $manifestService;
    }

    /**
     * Search published editions by title, code, category, or date range.
     * Note: Search is strictly scoped to edition metadata.
     */
    public function search(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('public_tenant');

        $validator = Validator::make($request->all(), [
            'q' => 'nullable|string|max:80',
            'category' => 'nullable|string|max:50',
            'from_date' => 'nullable|date_format:Y-m-d',
            'to_date' => 'nullable|date_format:Y-m-d|after_or_equal:from_date',
            'language' => 'nullable|string|max:10',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid search parameters.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $params = $validator->validated();
        $perPage = (int) ($params['per_page'] ?? 10);

        $paginator = $this->searchService->search($tenant, $params, $perPage);

        $results = collect($paginator->items())->map(function ($ed) {
            return [
                'id' => $ed->id,
                'edition_code' => $ed->edition_code,
                'edition_title' => $ed->edition_title ?? $ed->edition_name,
                'edition_name' => $ed->edition_name,
                'category' => $ed->category ?? 'Main Edition',
                'description' => $ed->description,
                'publish_date' => $ed->publish_date ? $ed->publish_date->format('Y-m-d') : null,
                'total_pages' => $ed->total_pages,
                'thumbnail_url' => $this->manifestService->sanitizePublicUrl($ed->thumbnail_path),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'query' => $params['q'] ?? '',
                'search_mode' => 'edition_metadata', // Explicitly informs frontend/client of metadata indexing scope
                'results' => $results,
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
