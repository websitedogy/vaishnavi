<?php

namespace App\Modules\SuperAdmin\Services;

use App\Modules\SuperAdmin\Models\InfrastructureService;
use App\Modules\SuperAdmin\Models\InfrastructureAlert;
use App\Modules\SuperAdmin\Models\DeploymentLog;
use App\Shared\Helpers\AuditHelper;
use Carbon\Carbon;

class CloudMonitoringService
{
    public function getSystemHealthOverview(): array
    {
        $services = InfrastructureService::all();
        $activeAlerts = InfrastructureAlert::where('status', 'active')->orderByDesc('triggered_at')->get();
        $recentDeployments = DeploymentLog::orderByDesc('started_at')->limit(5)->get();

        $healthyCount = $services->where('status', 'healthy')->count();
        $totalServices = $services->count();

        return [
            'overall_status' => ($activeAlerts->where('severity', 'critical')->count() > 0) ? 'critical' : ($healthyCount === $totalServices ? 'healthy' : 'degraded'),
            'healthy_services_ratio' => "{$healthyCount}/{$totalServices}",
            'active_alerts_count' => $activeAlerts->count(),
            'critical_alerts_count' => $activeAlerts->where('severity', 'critical')->count(),
            'services' => $services,
            'active_alerts' => $activeAlerts,
            'recent_deployments' => $recentDeployments,
            'cloudwatch_telemetry' => [
                'api_p95_latency_ms' => 45,
                'cdn_cache_hit_ratio_percent' => 96.8,
                'db_connections_active' => 38,
                'redis_memory_used_mb' => 256,
                'sqs_queue_depth' => 3,
            ]
        ];
    }

    public function acknowledgeAlert(int $alertId, ?int $adminId = null): InfrastructureAlert
    {
        $alert = InfrastructureAlert::findOrFail($alertId);
        $alert->status = 'acknowledged';
        $alert->save();

        AuditHelper::log($adminId, 'INFRA_ALERT_ACKNOWLEDGED', 'InfrastructureAlert', $alert->id, "Alert '{$alert->title}' acknowledged.");

        return $alert;
    }

    public function resolveAlert(int $alertId, ?int $adminId = null): InfrastructureAlert
    {
        $alert = InfrastructureAlert::findOrFail($alertId);
        $alert->status = 'resolved';
        $alert->resolved_at = Carbon::now();
        $alert->resolved_by_admin_id = $adminId;
        $alert->save();

        AuditHelper::log($adminId, 'INFRA_ALERT_RESOLVED', 'InfrastructureAlert', $alert->id, "Alert '{$alert->title}' resolved.");

        return $alert;
    }
}
