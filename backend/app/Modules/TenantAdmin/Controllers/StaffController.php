<?php

namespace App\Modules\TenantAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TenantAdmin\Services\StaffService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StaffController extends Controller
{
    protected StaffService $staffService;

    public function __construct(StaffService $staffService)
    {
        $this->staffService = $staffService;
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $result = $this->staffService->listStaff($tenantId);

        return response()->json([
            'success' => true,
            'data' => $result['staff'],
            'roles' => $result['roles'],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:owner,admin,editor,sub_editor,designer,reporter,viewer',
            'permissions' => 'nullable|array',
        ]);

        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $staff = $this->staffService->createStaff($tenantId, $validated, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Staff user invited successfully.',
            'data' => $staff,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'password' => 'nullable|string|min:8',
            'role' => 'sometimes|string|in:owner,admin,editor,sub_editor,designer,reporter,viewer',
            'permissions' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $staff = $this->staffService->updateStaff($tenantId, $id, $validated, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Staff user updated.',
            'data' => $staff,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $this->staffService->deleteStaff($tenantId, $id, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Staff access removed.',
        ]);
    }
}
