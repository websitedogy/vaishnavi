<?php

namespace App\Modules\TenantAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TenantAdmin\Services\PdfUploadService;
use App\Modules\TenantAdmin\Services\LocalPdfProcessingService;
use App\Modules\TenantAdmin\Models\Edition;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PdfUploadController extends Controller
{
    protected PdfUploadService $uploadService;
    protected LocalPdfProcessingService $processingService;

    public function __construct(PdfUploadService $uploadService, LocalPdfProcessingService $processingService)
    {
        $this->uploadService = $uploadService;
        $this->processingService = $processingService;
    }

    public function upload(Request $request, int $editionId): JsonResponse
    {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:262144', // 256MB max
        ]);

        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $result = $this->uploadService->uploadForEdition($tenantId, $editionId, $request->file('pdf_file'), $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'PDF uploaded and processed successfully.',
            'data' => $result,
        ]);
    }

    public function processingStatus(Request $request, int $editionId): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $edition = Edition::where('tenant_id', $tenantId)->findOrFail($editionId);
        $upload = $edition->uploads()->latest()->first();

        return response()->json([
            'success' => true,
            'data' => [
                'edition_id' => $edition->id,
                'edition_code' => $edition->edition_code,
                'status' => $edition->status,
                'total_pages' => $edition->total_pages,
                'file_size_mb' => $edition->file_size_mb,
                'last_upload' => $upload,
            ],
        ]);
    }

    public function retryProcessing(Request $request, int $editionId): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $result = $this->processingService->retryProcessing($tenantId, $editionId);

        return response()->json([
            'success' => true,
            'message' => 'Processing job restarted.',
            'data' => $result,
        ]);
    }
}
