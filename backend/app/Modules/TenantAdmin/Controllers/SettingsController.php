<?php

namespace App\Modules\TenantAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TenantAdmin\Services\TenantSettingsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    protected TenantSettingsService $settingsService;

    public function __construct(TenantSettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $settings = $this->settingsService->getSettings($tenantId);

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $validated = $request->validate([
            'settings' => 'required|array',
        ]);

        $settings = $this->settingsService->updateSettings($tenantId, $validated['settings'], $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Publishing settings updated.',
            'data' => $settings,
        ]);
    }
}
