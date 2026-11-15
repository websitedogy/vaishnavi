<?php

namespace App\Modules\TenantAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TenantAdmin\Services\PageManagementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    protected PageManagementService $pageService;

    public function __construct(PageManagementService $pageService)
    {
        $this->pageService = $pageService;
    }

    public function index(Request $request, int $editionId): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $pages = $this->pageService->getPages($tenantId, $editionId);

        return response()->json([
            'success' => true,
            'data' => $pages,
        ]);
    }

    public function reorder(Request $request, int $editionId): JsonResponse
    {
        $validated = $request->validate([
            'page_ids' => 'required|array',
            'page_ids.*' => 'integer',
        ]);

        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $pages = $this->pageService->reorderPages($tenantId, $editionId, $validated['page_ids'], $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Pages reordered successfully.',
            'data' => $pages,
        ]);
    }

    public function rotate(Request $request, int $editionId, int $pageId): JsonResponse
    {
        $validated = $request->validate([
            'angle' => 'nullable|integer|in:90,180,270',
        ]);

        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $angle = (int)($validated['angle'] ?? 90);
        $page = $this->pageService->rotatePage($tenantId, $editionId, $pageId, $angle, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => "Page rotated by {$angle}°.",
            'data' => $page,
        ]);
    }

    public function toggleVisibility(Request $request, int $editionId, int $pageId): JsonResponse
    {
        $validated = $request->validate([
            'is_visible' => 'required|boolean',
        ]);

        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $page = $this->pageService->toggleVisibility($tenantId, $editionId, $pageId, $validated['is_visible'], $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Page visibility updated.',
            'data' => $page,
        ]);
    }

    public function replace(Request $request, int $editionId, int $pageId): JsonResponse
    {
        $request->validate([
            'page_image' => 'required|image|mimes:png,jpg,jpeg,webp|max:10240',
        ]);

        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $page = $this->pageService->replacePage($tenantId, $editionId, $pageId, $request->file('page_image'), $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Page image replaced successfully.',
            'data' => $page,
        ]);
    }

    public function destroy(Request $request, int $editionId, int $pageId): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $this->pageService->deletePage($tenantId, $editionId, $pageId, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Page deleted and remaining pages renumbered.',
        ]);
    }
}
