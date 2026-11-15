<?php

namespace App\Modules\SuperAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SuperAdmin\Models\NewspaperEdition;
use App\Modules\SuperAdmin\Models\PdfProcessingJob;
use App\Modules\SuperAdmin\Models\StorageUsageRecord;
use App\Modules\SuperAdmin\Services\PdfOperationsService;
use App\Shared\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperationsController extends Controller
{
    use ApiResponseTrait;

    protected PdfOperationsService $operationsService;

    public function __construct(PdfOperationsService $operationsService)
    {
        $this->operationsService = $operationsService;
    }

    public function stats()
    {
        $stats = $this->operationsService->getOperationsStats();
        return $this->successResponse($stats);
    }

    public function editions(Request $request)
    {
        $query = NewspaperEdition::with(['tenant', 'jobs']);

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $editions = $query->orderByDesc('id')->paginate($request->integer('per_page', 15));

        return $this->successResponse($editions);
    }

    public function jobs(Request $request)
    {
        $query = PdfProcessingJob::with(['edition', 'tenant']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $jobs = $query->orderByDesc('id')->paginate($request->integer('per_page', 15));

        return $this->successResponse($jobs);
    }

    public function retryJob(Request $request, int $id)
    {
        $adminId = $request->user() ? $request->user()->id : null;
        $job = $this->operationsService->retryJob($id, $adminId);

        return $this->successResponse($job, 'PDF processing job has been requeued for conversion.');
    }

    public function purgeDlq(Request $request)
    {
        $adminId = $request->user() ? $request->user()->id : null;
        $count = $this->operationsService->purgeDeadLetterQueue($adminId);

        return $this->successResponse(['purged_count' => $count], "{$count} Dead Letter Queue jobs purged.");
    }

    public function storageUsage(Request $request)
    {
        $records = DB::table('tenants')
            ->select('id', 'name', 'slug', 'storage_used_mb', 'storage_limit_mb')
            ->orderByDesc('storage_used_mb')
            ->get();

        return $this->successResponse($records);
    }
}
