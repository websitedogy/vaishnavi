<?php

namespace App\Http\Controllers\TenantAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TenantAdmin\Concerns\ResolvesTenant;
use App\Models\Advertisement;
use App\Models\NewsPost;
use App\Models\TenantTrafficStat;
use App\Services\TenantDashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TenantAnalyticsController extends Controller
{
    use ResolvesTenant;

    public function __construct(protected TenantDashboardService $dashboard) {}

    public function index(Request $request)
    {
        $tenant = $this->tenant();
        $days = (int) $request->get('days', 30);
        $chart = $this->dashboard->analyticsChart($tenant, min($days, 30));

        $topArticles = NewsPost::where('tenant_id', $tenant->id)
            ->orderByDesc('views')->limit(10)->get(['title', 'views', 'status']);

        $traffic = TenantTrafficStat::where('tenant_id', $tenant->id)
            ->where('date', '>=', Carbon::today()->subDays($days))
            ->orderBy('date')
            ->get();

        $revenue = Advertisement::where('tenant_id', $tenant->id)->sum('revenue');

        return view('tenant-admin.analytics.index', compact('chart', 'topArticles', 'traffic', 'revenue', 'days'));
    }
}
