<?php

namespace App\Modules\TenantAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TenantAdmin\Services\PublishingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PublishingController extends Controller
{
    protected PublishingService $publishingService;

    public function __construct(PublishingService $publishingService)
    {
        $this->publishingService = $publishingService;
    }

    public function schedule(Request $request, int $editionId): JsonResponse
    {
        $validated = $request->validate([
            'scheduled_at' => 'required|date',
            'timezone' => 'nullable|string|max:50',
        ]);

        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $tz = $validated['timezone'] ?? 'Asia/Kolkata';
        $edition = $this->publishingService->scheduleEdition($tenantId, $editionId, $validated['scheduled_at'], $tz, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Edition scheduled for automated publication.',
            'data' => $edition,
        ]);
    }

    public function publishNow(Request $request, int $editionId): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $edition = $this->publishingService->publishNow($tenantId, $editionId, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Edition is now live and published.',
            'data' => $edition,
        ]);
    }

    public function unpublish(Request $request, int $editionId): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $edition = $this->publishingService->unpublish($tenantId, $editionId, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Edition unpublished and returned to review.',
            'data' => $edition,
        ]);
    }

    public function cancelSchedule(Request $request, int $editionId): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $edition = $this->publishingService->cancelSchedule($tenantId, $editionId, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Scheduled publication cancelled.',
            'data' => $edition,
        ]);
    }

    public function calendar(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $year = (int)($request->query('year', date('Y')));
        $month = (int)($request->query('month', date('n')));

        $result = $this->publishingService->getCalendar($tenantId, $year, $month);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $history = $this->publishingService->getHistory($tenantId);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}
