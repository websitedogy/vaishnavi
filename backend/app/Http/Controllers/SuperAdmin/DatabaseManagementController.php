<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\LoginLog;
use App\Models\SupportTicket;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DatabaseManagementController extends Controller
{
    public function index()
    {
        $tables = DB::select('SHOW TABLE STATUS');
        $dbName = DB::getDatabaseName();
        $rowCountEstimate = collect($tables)->sum('Rows');
        $sizeBytes = collect($tables)->sum(function ($table) {
            return ($table->Data_length ?? 0) + ($table->Index_length ?? 0);
        });

        $stats = [
            'database' => $dbName,
            'tables' => count($tables),
            'rows' => (int) $rowCountEstimate,
            'size_mb' => round($sizeBytes / 1024 / 1024, 2),
        ];

        return view('super-admin.database.index', compact('stats'));
    }

    public function cleanup(Request $request)
    {
        LoginLog::where('attempted_at', '<', now()->subDays(60))->delete();
        ActivityLog::where('created_at', '<', now()->subDays(90))->delete();
        AuditLog::where('performed_at', '<', now()->subDays(120))->delete();
        SupportTicket::where('status', 'closed')->where('updated_at', '<', now()->subDays(120))->delete();

        AuditLogger::log('database_cleanup', 'database', null, 'Old logs/tickets cleaned up.', [], $request);

        return back()->with('success', 'Database cleanup completed.');
    }

    public function optimize(Request $request)
    {
        DB::statement('OPTIMIZE TABLE activity_logs, login_logs, audit_logs');

        AuditLogger::log('database_optimized', 'database', null, 'Database tables optimized.', [], $request);

        return back()->with('success', 'Database optimization completed.');
    }
}
