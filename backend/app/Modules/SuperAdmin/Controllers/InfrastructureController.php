<?php

namespace App\Modules\SuperAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SuperAdmin\Models\InfrastructureService;
use App\Modules\SuperAdmin\Models\InfrastructureAlert;
use App\Modules\SuperAdmin\Models\DeploymentLog;
use App\Modules\SuperAdmin\Services\CloudMonitoringService;
use App\Shared\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class InfrastructureController extends Controller
{
    use ApiResponseTrait;

    protected CloudMonitoringService $cloudService;

    public function __construct(CloudMonitoringService $cloudService)
    {
        $this->cloudService = $cloudService;
    }

    public function overview()
    {
        $data = $this->cloudService->getSystemHealthOverview();
        return $this->successResponse($data);
    }

    public function services()
    {
        $services = InfrastructureService::with('alerts')->get();
        return $this->successResponse($services);
    }

    public function alerts(Request $request)
    {
        $query = InfrastructureAlert::with('service');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $alerts = $query->orderByDesc('triggered_at')->paginate($request->integer('per_page', 15));

        return $this->successResponse($alerts);
    }

    public function acknowledgeAlert(Request $request, int $id)
    {
        $adminId = $request->user() ? $request->user()->id : null;
        $alert = $this->cloudService->acknowledgeAlert($id, $adminId);

        return $this->successResponse($alert, 'Infrastructure alert acknowledged.');
    }

    public function resolveAlert(Request $request, int $id)
    {
        $adminId = $request->user() ? $request->user()->id : null;
        $alert = $this->cloudService->resolveAlert($id, $adminId);

        return $this->successResponse($alert, 'Infrastructure alert resolved.');
    }

    public function deployments()
    {
        $deployments = DeploymentLog::orderByDesc('started_at')->limit(20)->get();
        return $this->successResponse($deployments);
    }
}
