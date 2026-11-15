<?php

namespace App\Http\Controllers\TenantAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TenantAdmin\Concerns\ResolvesTenant;
use App\Services\TenantDashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ResolvesTenant;

    public function __construct(protected TenantDashboardService $dashboard) {}

    public function index()
    {
        $tenant = $this->tenant();
        $user = $this->user();

        return view('tenant-admin.dashboard', [
            'user' => $user,
            'tenant' => $tenant,
            'stats' => $this->dashboard->stats($tenant),
            'trending' => $this->dashboard->trending($tenant),
            'latestComments' => $this->dashboard->latestComments($tenant),
            'pendingApprovals' => $this->dashboard->pendingApprovals($tenant),
            'authorActivity' => $this->dashboard->authorActivity($tenant),
            'chart' => $this->dashboard->analyticsChart($tenant),
        ]);
    }

    public function analytics(Request $request)
    {
        return response()->json($this->dashboard->analyticsChart($this->tenant(), (int) $request->get('days', 7)));
    }
}
