<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with(['tenant', 'superAdmin'])->latest();

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        $logs = $query->paginate(20)->withQueryString();
        $types = ActivityLog::query()->select('type')->distinct()->pluck('type');

        return view('super-admin.activity-logs.index', compact('logs', 'types'));
    }
}
