<?php

namespace App\Modules\TenantAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TenantAdmin\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function editions(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $filters = $request->only(['date_from', 'date_to', 'status']);
        $result = $this->reportService->getEditionReports($tenantId, $filters);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function storage(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $result = $this->reportService->getStorageReports($tenantId);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function activity(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $logs = $this->reportService->getActivityReports($tenantId);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $type = $request->query('type', 'editions');
        $csvData = $this->reportService->exportCsv($tenantId, $type);

        $filename = "{$type}_report_" . date('Ymd_His') . ".csv";

        return response()->streamDownload(function () use ($csvData) {
            echo $csvData;
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
