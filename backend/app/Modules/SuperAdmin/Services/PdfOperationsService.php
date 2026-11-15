<?php

namespace App\Modules\SuperAdmin\Services;

use App\Modules\SuperAdmin\Models\NewspaperEdition;
use App\Modules\SuperAdmin\Models\PdfProcessingJob;
use App\Modules\SuperAdmin\Models\StorageUsageRecord;
use App\Shared\Helpers\AuditHelper;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PdfOperationsService
{
    public function getOperationsStats(): array
    {
        $totalEditions = NewspaperEdition::count();
        $totalJobs = PdfProcessingJob::count();
        $completedJobs = PdfProcessingJob::where('status', 'completed')->count();
        $failedJobs = PdfProcessingJob::where('status', 'failed')->count();
        $queuedJobs = PdfProcessingJob::whereIn('status', ['queued', 'processing', 'retrying'])->count();
        $deadLetterJobs = PdfProcessingJob::where('status', 'dead_letter')->count();

        $storageStats = DB::table('tenants')
            ->selectRaw('SUM(storage_used_mb) as total_used_mb, SUM(storage_limit_mb) as total_limit_mb')
            ->first();

        return [
            'total_editions' => $totalEditions,
            'total_jobs' => $totalJobs,
            'completed_jobs' => $completedJobs,
            'failed_jobs' => $failedJobs,
            'queued_jobs' => $queuedJobs,
            'dead_letter_jobs' => $deadLetterJobs,
            'success_rate' => $totalJobs > 0 ? round(($completedJobs / $totalJobs) * 100, 2) : 100.0,
            'storage' => [
                'used_mb' => round(floatval($storageStats->total_used_mb ?? 0), 2),
                'limit_mb' => round(floatval($storageStats->total_limit_mb ?? 0), 2),
                'used_percentage' => ($storageStats && $storageStats->total_limit_mb > 0)
                    ? round(($storageStats->total_used_mb / $storageStats->total_limit_mb) * 100, 2)
                    : 0,
            ],
            'workers' => [
                'total_fargate_workers' => 6,
                'active_workers' => 4,
                'idle_workers' => 2,
                'avg_conversion_time_seconds' => 45.2,
            ]
        ];
    }

    public function retryJob(int $jobId, ?int $adminId = null): PdfProcessingJob
    {
        $job = PdfProcessingJob::findOrFail($jobId);
        $job->status = 'queued';
        $job->attempts = $job->attempts + 1;
        $job->error_message = null;
        $job->started_at = null;
        $job->completed_at = null;
        $job->save();

        AuditHelper::log($adminId, 'PDF_JOB_RETRY', 'PdfProcessingJob', $job->id, "Retried PDF processing job #{$job->id} [UUID: {$job->job_uuid}].");

        return $job;
    }

    public function purgeDeadLetterQueue(?int $adminId = null): int
    {
        $count = PdfProcessingJob::where('status', 'dead_letter')->count();
        PdfProcessingJob::where('status', 'dead_letter')->delete();

        AuditHelper::log($adminId, 'DLQ_PURGED', 'PdfProcessingJob', null, "Purged {$count} dead-lettered PDF conversion jobs.");

        return $count;
    }
}
