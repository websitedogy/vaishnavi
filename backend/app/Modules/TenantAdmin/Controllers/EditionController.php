<?php

namespace App\Modules\TenantAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TenantAdmin\Services\EditionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EditionController extends Controller
{
    protected EditionService $editionService;

    public function __construct(EditionService $editionService)
    {
        $this->editionService = $editionService;
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $filters = $request->only(['status', 'category', 'search', 'date', 'per_page']);

        $result = $this->editionService->listEditions($tenantId, $filters);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'pagination' => $result['pagination'],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'edition_name' => 'required|string|max:255',
            'edition_title' => 'nullable|string|max:255',
            'edition_code' => 'nullable|string|max:50',
            'edition_language' => 'nullable|string|max:10',
            'category' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'publish_date' => 'nullable|date',
        ]);

        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $edition = $this->editionService->createEdition($tenantId, $validated, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Edition draft initialized successfully.',
            'data' => $edition,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $edition = $this->editionService->getEdition($tenantId, $id);

        return response()->json([
            'success' => true,
            'data' => $edition,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $validated = $request->validate([
            'edition_name' => 'sometimes|string|max:255',
            'edition_title' => 'nullable|string|max:255',
            'edition_language' => 'nullable|string|max:10',
            'category' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'publish_date' => 'nullable|date',
        ]);

        $edition = $this->editionService->updateEdition($tenantId, $id, $validated, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Edition updated successfully.',
            'data' => $edition,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $this->editionService->deleteEdition($tenantId, $id, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Draft edition deleted.',
        ]);
    }

    public function duplicate(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $duplicate = $this->editionService->duplicateEdition($tenantId, $id, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Edition duplicated successfully.',
            'data' => $duplicate,
        ], 201);
    }

    public function archive(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $archived = $this->editionService->archiveEdition($tenantId, $id, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Edition moved to archive.',
            'data' => $archived,
        ]);
    }

    public function downloadOriginalPdf(Request $request, int $id)
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $edition = $this->editionService->getEdition($tenantId, $id);

        if (str_contains($edition->original_pdf_path ?? '', '..')) {
            return response()->json([
                'success' => false,
                'message' => 'Security Error: Path traversal detected.',
            ], 400);
        }

        if (!$edition->original_pdf_path || !\Illuminate\Support\Facades\Storage::disk('local')->exists($edition->original_pdf_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Original PDF file was not found on secure storage.',
            ], 404);
        }

        \App\Modules\TenantAdmin\Services\TenantAuditHelper::log(
            $tenantId,
            $request->user()->id,
            'pdf_downloaded',
            'editions',
            "Downloaded secure original PDF for edition {$edition->edition_code}."
        );

        $downloadFilename = ($edition->edition_code ?: 'edition') . '.pdf';

        return \Illuminate\Support\Facades\Storage::disk('local')->download(
            $edition->original_pdf_path,
            $downloadFilename,
            [
                'Content-Type' => 'application/pdf',
                'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            ]
        );
    }
}
