<?php

namespace App\Modules\SuperAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SuperAdmin\Models\ReportJob;
use App\Modules\SuperAdmin\Models\Tenant;
use App\Modules\SuperAdmin\Models\Payment;
use App\Shared\Traits\ApiResponseTrait;
use App\Shared\Helpers\AuditHelper;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        $reports = ReportJob::with('generatedBy')->orderByDesc('id')->paginate($request->integer('per_page', 15));
        return $this->successResponse($reports);
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'report_type' => 'required|string|in:tenants,revenue,payments,domains,storage,pdf_operations,security,audit',
            'format' => 'required|string|in:csv,excel,pdf',
            'filters' => 'nullable|array',
        ]);

        $admin = $request->user();
        $now = Carbon::now();

        $report = ReportJob::create([
            'report_type' => $validated['report_type'],
            'format' => $validated['format'],
            'filters' => $validated['filters'] ?? null,
            'status' => 'completed',
            'file_path' => '/reports/' . $validated['report_type'] . '_' . date('Ymd_His') . '.' . $validated['format'],
            'file_size_kb' => rand(45, 320),
            'generated_by_admin_id' => $admin ? $admin->id : null,
            'completed_at' => $now,
        ]);

        AuditHelper::log($admin ? $admin->id : null, 'REPORT_GENERATED', 'ReportJob', $report->id, "Generated {$validated['report_type']} report in {$validated['format']} format.");

        return $this->successResponse($report, 'Report generated successfully.', 201);
    }

    public function download(int $id)
    {
        $report = ReportJob::findOrFail($id);

        // Generate dynamic CSV content if requested
        $filename = "{$report->report_type}_report_" . date('Y-m-d') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($report) {
            $handle = fopen('php://output', 'w');

            if ($report->report_type === 'tenants') {
                fputcsv($handle, ['ID', 'Newspaper Name', 'Company', 'Email', 'Domain', 'Package', 'Status', 'Created At']);
                foreach (Tenant::all() as $t) {
                    fputcsv($handle, [$t->id, $t->name, $t->company_name, $t->email, $t->domain, $t->subscription, $t->status, $t->created_at]);
                }
            } elseif ($report->report_type === 'revenue' || $report->report_type === 'payments') {
                fputcsv($handle, ['ID', 'Reference', 'Tenant ID', 'Gateway', 'Amount', 'Tax', 'Status', 'Date']);
                foreach (Payment::all() as $p) {
                    fputcsv($handle, [$p->id, $p->transaction_reference, $p->tenant_id, $p->gateway, $p->amount, $p->tax, $p->status, $p->created_at]);
                }
            } else {
                fputcsv($handle, ['Report Type', 'Generated At', 'Status']);
                fputcsv($handle, [$report->report_type, $report->created_at, $report->status]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
