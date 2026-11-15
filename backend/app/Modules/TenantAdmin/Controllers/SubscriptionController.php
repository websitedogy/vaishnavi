<?php

namespace App\Modules\TenantAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TenantAdmin\Services\UsageTrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    protected UsageTrackingService $usageService;

    public function __construct(UsageTrackingService $usageService)
    {
        $this->usageService = $usageService;
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $data = $this->usageService->getSubscriptionUsage($tenantId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function upgradeRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'package_id' => 'required|integer',
            'notes' => 'nullable|string',
        ]);

        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $upgradeRequest = $this->usageService->submitUpgradeRequest(
            $tenantId,
            $validated['package_id'],
            $validated['notes'] ?? null,
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Upgrade request submitted to platform administration.',
            'data' => $upgradeRequest,
        ], 201);
    }
}
