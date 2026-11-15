<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\NewsPost;
use App\Models\Tenant;
use App\Models\TenantTrafficStat;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function index()
    {
        $trafficRows = TenantTrafficStat::with('tenant')
            ->where('date', '>=', now()->subDays(30)->toDateString())
            ->get();

        $tenantTraffic = $trafficRows
            ->groupBy('tenant_id')
            ->map(function ($rows) {
                $tenant = $rows->first()?->tenant;

                return [
                    'tenant' => $tenant?->name ?? 'Unknown',
                    'visitors' => $rows->sum('visitors'),
                    'page_views' => $rows->sum('page_views'),
                ];
            })
            ->sortByDesc('visitors')
            ->values();

        $dailyVisitors = collect(range(6, 0))
            ->map(function ($daysAgo) use ($trafficRows) {
                $date = now()->subDays($daysAgo)->toDateString();

                return [
                    'label' => Carbon::parse($date)->format('d M'),
                    'visitors' => $trafficRows->where('date', $date)->sum('visitors'),
                ];
            });

        $popularTenants = $tenantTraffic->take(5);

        $topViewedNews = NewsPost::with('tenant')
            ->orderByDesc('views')
            ->limit(8)
            ->get();

        $monthlyIncome = collect(range(5, 0))->map(function ($monthsAgo) {
            $month = now()->subMonths($monthsAgo);
            $count = Tenant::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();

            $value = Tenant::whereYear('created_at', '<=', $month->year)
                ->whereMonth('created_at', '<=', $month->month)
                ->sum('subscription_amount');

            return [
                'label' => $month->format('M'),
                'value' => $value > 0 ? round((float) $value, 2) : ($count * 60),
            ];
        });

        $subscriptionGrowth = [
            'current' => Tenant::count(),
            'last_month' => Tenant::where('created_at', '<', now()->startOfMonth())->count(),
        ];

        return view('super-admin.analytics.index', compact(
            'tenantTraffic',
            'dailyVisitors',
            'popularTenants',
            'topViewedNews',
            'monthlyIncome',
            'subscriptionGrowth'
        ));
    }
}
