<?php

namespace App\Modules\TenantAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TenantAdmin\Services\MediaLibraryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MediaLibraryController extends Controller
{
    protected MediaLibraryService $mediaService;

    public function __construct(MediaLibraryService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $filters = $request->only(['type', 'search', 'per_page']);

        $result = $this->mediaService->listMedia($tenantId, $filters);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'pagination' => $result['pagination'],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:20480', // 20MB
        ]);

        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $media = $this->mediaService->uploadMedia($tenantId, $request->file('file'), $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Media file uploaded successfully.',
            'data' => $media,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $data = $request->only(['name', 'alt_text']);
        $media = $this->mediaService->updateMedia($tenantId, $id, $data, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Media updated.',
            'data' => $media,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $this->mediaService->deleteMedia($tenantId, $id, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Media file removed.',
        ]);
    }
}
