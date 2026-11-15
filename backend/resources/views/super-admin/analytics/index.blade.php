@extends('layouts.super-admin')

@section('title', 'Analytics & Reports')
@section('page-title', 'Analytics & Reports')
@section('page-subtitle', 'Traffic, revenue, and growth insights')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-900 mb-4">Daily Visitors (Last 7 days)</h2>
        @php $maxDaily = max($dailyVisitors->max('visitors'), 1); @endphp
        <div class="flex items-end gap-3 h-44">
            @foreach ($dailyVisitors as $day)
                <div class="flex-1 flex flex-col items-center gap-2">
                    <div class="w-full bg-slate-100 rounded-t-md flex items-end h-28">
                        <div class="w-full bg-brand-600 rounded-t-md" style="height: {{ max(($day['visitors'] / $maxDaily) * 100, 6) }}%"></div>
                    </div>
                    <span class="text-xs text-slate-500">{{ $day['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-900 mb-4">Revenue Analytics</h2>
        @php $maxMonthly = max($monthlyIncome->max('value'), 1); @endphp
        <div class="space-y-3">
            @foreach ($monthlyIncome as $month)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-slate-600">{{ $month['label'] }}</span>
                        <span class="font-medium text-slate-900">${{ number_format($month['value'], 0) }}</span>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full">
                        <div class="h-2 bg-emerald-500 rounded-full" style="width: {{ ($month['value'] / $maxMonthly) * 100 }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="mt-4 text-sm text-slate-500">
            Subscription growth: <span class="font-semibold text-slate-900">{{ $subscriptionGrowth['current'] }}</span> total tenants
            (was {{ $subscriptionGrowth['last_month'] }} before this month).
        </p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-900 mb-4">Tenant-wise Traffic / Popular Tenants</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b">
                        <th class="py-2">Tenant</th>
                        <th class="py-2">Visitors</th>
                        <th class="py-2">Page Views</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tenantTraffic->take(8) as $row)
                        <tr class="border-b last:border-0">
                            <td class="py-2 font-medium text-slate-800">{{ $row['tenant'] }}</td>
                            <td class="py-2">{{ number_format($row['visitors']) }}</td>
                            <td class="py-2">{{ number_format($row['page_views']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-900 mb-4">Top Viewed News</h2>
        <div class="space-y-3">
            @forelse ($topViewedNews as $post)
                <div class="p-3 rounded-lg bg-slate-50">
                    <p class="font-medium text-slate-800">{{ $post->title }}</p>
                    <p class="text-xs text-slate-500 mt-1">{{ $post->tenant?->name ?? 'Unknown tenant' }} · {{ number_format($post->views) }} views</p>
                </div>
            @empty
                <p class="text-sm text-slate-500">No news data available.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
