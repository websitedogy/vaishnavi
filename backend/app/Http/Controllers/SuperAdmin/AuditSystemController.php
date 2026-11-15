<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LoginLog;
use Illuminate\Http\Request;

class AuditSystemController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('superAdmin')->latest('performed_at');

        if ($action = $request->get('action')) {
            $query->where('action', $action);
        }

        $auditLogs = $query->paginate(20)->withQueryString();
        $actions = AuditLog::query()->select('action')->distinct()->pluck('action');
        $loginLogs = LoginLog::latest('attempted_at')->limit(15)->get();

        return view('super-admin.audit.index', compact('auditLogs', 'actions', 'loginLogs'));
    }
}
