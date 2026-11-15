<?php

namespace App\Modules\TenantAdmin\Services;

use App\Modules\TenantAdmin\Models\Edition;
use App\Modules\TenantAdmin\Models\EditionPage;
use App\Modules\TenantAdmin\Models\PdfUpload;
use App\Modules\TenantAdmin\Models\TenantActivityLog;
use App\Modules\TenantAdmin\Models\TenantNotification;
use App\Modules\SuperAdmin\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;

class DashboardService
{
    public function getDashboardData(int $tenantId): array
    {
        $tenant = Tenant::findOrFail($tenantId);
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();

        // 1. KPI Counts
        $totalEditions = Edition::where('tenant_id', $tenantId)->count();
        $publishedEditions = Edition::where('tenant_id', $tenantId)->where('status', 'published')->count();
        $draftEditions = Edition::where('tenant_id', $tenantId)->where('status', 'draft')->count();
        $scheduledEditions = Edition::where('tenant_id', $tenantId)->where('status', 'scheduled')->count();
        $processingEditions = Edition::where('tenant_id', $tenantId)->whereIn('status', ['uploaded', 'processing'])->count();
        $failedEditions = Edition::where('tenant_id', $tenantId)->where('status', 'processing_failed')->count();

        $totalUploads = PdfUpload::where('tenant_id', $tenantId)->count();
        $totalPages = EditionPage::where('tenant_id', $tenantId)->count();
        $monthlyEditions = Edition::where('tenant_id', $tenantId)->where('created_at', '>=', $startOfMonth)->count();

        $activeStaff = User::where('tenant_id', $tenantId)->where('is_active', true)->count();
        $unreadNotifications = TenantNotification::where('tenant_id', $tenantId)->where('is_read', false)->count();

        // 2. Storage Calculation
        $storageUsedMb = (float)($tenant->storage_used_mb ?? 0);
        $storageLimitMb = (int)($tenant->storage_limit_mb ?? 51200); // 50GB default
        $storageUsedGb = round($storageUsedMb / 1024, 2);
        $storageLimitGb = round($storageLimitMb / 1024, 1);
        $storagePercent = $storageLimitMb > 0 ? min(round(($storageUsedMb / $storageLimitMb) * 100, 1), 100) : 0;

        // 3. Recent Editions
        $recentEditions = Edition::where('tenant_id', $tenantId)
            ->with(['author:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // 4. Latest PDF Uploads
        $latestUploads = PdfUpload::where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // 5. Processing Queue
        $processingQueue = Edition::where('tenant_id', $tenantId)
            ->whereIn('status', ['uploaded', 'processing', 'processing_failed'])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        // 6. Upcoming Scheduled Editions
        $upcomingScheduled = Edition::where('tenant_id', $tenantId)
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>=', $now)
            ->orderBy('scheduled_at', 'asc')
            ->limit(5)
            ->get();

        // 7. Recent Staff Activity Logs
        $recentActivities = TenantActivityLog::where('tenant_id', $tenantId)
            ->with(['user:id,name,role'])
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        // 8. 7-Day Publishing Trajectory
        $sevenDaysTrajectory = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i)->format('Y-m-d');
            $count = Edition::where('tenant_id', $tenantId)
                ->where('publish_date', $day)
                ->where('status', 'published')
                ->count();
            $sevenDaysTrajectory[] = [
                'date' => $day,
                'day_name' => $now->copy()->subDays($i)->format('D'),
                'published_count' => $count,
            ];
        }

        return [
            'kpis' => [
                'total_editions' => $totalEditions,
                'published_editions' => $publishedEditions,
                'draft_editions' => $draftEditions,
                'scheduled_editions' => $scheduledEditions,
                'processing_editions' => $processingEditions,
                'failed_editions' => $failedEditions,
                'total_pdf_uploads' => $totalUploads,
                'total_pages' => $totalPages,
                'monthly_editions' => $monthlyEditions,
                'active_staff' => $activeStaff,
                'unread_notifications' => $unreadNotifications,
                'storage' => [
                    'used_mb' => $storageUsedMb,
                    'limit_mb' => $storageLimitMb,
                    'used_gb' => $storageUsedGb,
                    'limit_gb' => $storageLimitGb,
                    'percent' => $storagePercent,
                ],
                'subscription' => [
                    'plan_name' => $tenant->subscription ?? 'Standard Plan',
                    'status' => $tenant->status,
                    'renewal_date' => $tenant->subscription_ends_at ? Carbon::parse($tenant->subscription_ends_at)->format('Y-m-d') : '2026-12-31',
                ],
            ],
            'recent_editions' => $recentEditions,
            'latest_uploads' => $latestUploads,
            'processing_queue' => $processingQueue,
            'upcoming_scheduled' => $upcomingScheduled,
            'recent_activities' => $recentActivities,
            'publishing_trajectory' => $sevenDaysTrajectory,
        ];
    }
}
