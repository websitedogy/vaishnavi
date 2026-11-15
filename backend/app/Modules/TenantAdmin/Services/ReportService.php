<?php

namespace App\Modules\TenantAdmin\Services;

use App\Modules\TenantAdmin\Models\Edition;
use App\Modules\TenantAdmin\Models\PdfUpload;
use App\Modules\TenantAdmin\Models\TenantActivityLog;
use App\Modules\SuperAdmin\Models\Tenant;
use Carbon\Carbon;

class ReportService
{
    public function getEditionReports(int $tenantId, array $filters = []): array
    {
        $query = Edition::where('tenant_id', $tenantId);

        if (!empty($filters['date_from'])) {
            $query->where('publish_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('publish_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $editions = $query->orderBy('publish_date', 'desc')->get();

        $byStatus = [
            'published' => $editions->where('status', 'published')->count(),
            'scheduled' => $editions->where('status', 'scheduled')->count(),
            'draft' => $editions->where('status', 'draft')->count(),
            'processing_failed' => $editions->where('status', 'processing_failed')->count(),
        ];

        $totalPages = $editions->sum('total_pages');
        $totalSizeMb = $editions->sum('file_size_mb');

        return [
            'summary' => [
                'total_editions' => $editions->count(),
                'by_status' => $byStatus,
                'total_pages' => $totalPages,
                'total_size_mb' => round($totalSizeMb, 2),
            ],
            'editions' => $editions,
        ];
    }

    public function getStorageReports(int $tenantId): array
    {
        $tenant = Tenant::findOrFail($tenantId);
        $usedMb = (float)($tenant->storage_used_mb ?? 0);
        $limitMb = (int)($tenant->storage_limit_mb ?? 51200);

        $uploads = PdfUpload::where('tenant_id', $tenantId)
            ->selectRaw('DATE(created_at) as upload_date, COUNT(*) as upload_count, SUM(file_size) as total_bytes')
            ->groupBy('upload_date')
            ->orderBy('upload_date', 'desc')
            ->limit(30)
            ->get();

        return [
            'storage' => [
                'used_mb' => $usedMb,
                'limit_mb' => $limitMb,
                'percent' => $limitMb > 0 ? min(round(($usedMb / $limitMb) * 100, 1), 100) : 0,
            ],
            'upload_timeline' => $uploads,
        ];
    }

    public function getActivityReports(int $tenantId, int $limit = 50)
    {
        return TenantActivityLog::where('tenant_id', $tenantId)
            ->with('user:id,name,role')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function exportCsv(int $tenantId, string $type): string
    {
        if ($type === 'editions') {
            $editions = Edition::where('tenant_id', $tenantId)->orderBy('publish_date', 'desc')->get();
            $csv = "ID,Edition Code,Edition Name,Publish Date,Pages,File Size (MB),Status,Published At\n";
            foreach ($editions as $e) {
                $csv .= "{$e->id},\"{$e->edition_code}\",\"{$e->edition_name}\",{$e->publish_date},{$e->total_pages},{$e->file_size_mb},{$e->status},{$e->published_at}\n";
            }
            return $csv;
        }

        if ($type === 'activity') {
            $logs = TenantActivityLog::where('tenant_id', $tenantId)->with('user')->orderBy('created_at', 'desc')->limit(500)->get();
            $csv = "ID,Action,Module,Description,Staff Member,IP Address,Timestamp\n";
            foreach ($logs as $l) {
                $user = $l->user ? $l->user->name : 'System';
                $csv .= "{$l->id},\"{$l->action}\",\"{$l->module}\",\"{$l->description}\",\"{$user}\",\"{$l->ip_address}\",{$l->created_at}\n";
            }
            return $csv;
        }

        return "No data\n";
    }
}
