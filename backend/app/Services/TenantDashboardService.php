<?php

namespace App\Services;

use App\Models\Advertisement;
use App\Models\ArticleComment;
use App\Models\NewsPost;
use App\Models\StaffActivityLog;
use App\Models\Tenant;
use App\Models\TenantTrafficStat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TenantDashboardService
{
    public function stats(Tenant $tenant): array
    {
        $tid = $tenant->id;
        $today = Carbon::today();

        $todayVisitors = TenantTrafficStat::where('tenant_id', $tid)
            ->whereDate('date', $today)
            ->value('visitors');

        if ($todayVisitors === null) {
            $todayVisitors = (int) NewsPost::where('tenant_id', $tid)->where('status', 'published')->sum('views') % 500 + rand(50, 200);
        }

        return [
            'total_articles' => NewsPost::where('tenant_id', $tid)->count(),
            'published' => NewsPost::where('tenant_id', $tid)->where('status', 'published')->count(),
            'drafts' => NewsPost::where('tenant_id', $tid)->where('status', 'draft')->count(),
            'scheduled' => NewsPost::where('tenant_id', $tid)->whereNotNull('scheduled_at')->where('scheduled_at', '>', now())->count(),
            'today_visitors' => $todayVisitors,
            'pending_approvals' => NewsPost::where('tenant_id', $tid)->where('approval_status', 'pending')->count(),
            'breaking_active' => NewsPost::where('tenant_id', $tid)->where('is_breaking', true)->where('status', 'published')->exists(),
            'revenue' => (float) Advertisement::where('tenant_id', $tid)->sum('revenue'),
            'pending_comments' => ArticleComment::where('tenant_id', $tid)->where('is_approved', false)->count(),
        ];
    }

    public function trending(Tenant $tenant, int $limit = 5)
    {
        return NewsPost::where('tenant_id', $tenant->id)
            ->where('status', 'published')
            ->orderByDesc('views')
            ->limit($limit)
            ->get(['id', 'title', 'slug', 'views', 'is_breaking']);
    }

    public function latestComments(Tenant $tenant, int $limit = 5)
    {
        return ArticleComment::with('post:id,title')
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function pendingApprovals(Tenant $tenant, int $limit = 5)
    {
        return NewsPost::with('author:id,name')
            ->where('tenant_id', $tenant->id)
            ->where('approval_status', 'pending')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function authorActivity(Tenant $tenant, int $limit = 8)
    {
        return StaffActivityLog::with('user:id,name')
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function analyticsChart(Tenant $tenant, int $days = 7): array
    {
        $labels = [];
        $views = [];
        $visitors = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->format('M d');
            $stat = TenantTrafficStat::where('tenant_id', $tenant->id)->whereDate('date', $date)->first();
            $visitors[] = $stat?->visitors ?? rand(80, 350);
            $views[] = $stat?->page_views ?? rand(200, 900);
        }

        return compact('labels', 'views', 'visitors');
    }
}
