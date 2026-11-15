<?php

namespace App\Modules\TenantAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TenantAdmin\Services\TemplateService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TemplateController extends Controller
{
    protected TemplateService $templateService;

    public function __construct(TemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $result = $this->templateService->listTemplates($tenantId);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function apply(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $result = $this->templateService->applyTemplate($tenantId, $id, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Template applied successfully.',
            'data' => $result,
        ]);
    }
}
