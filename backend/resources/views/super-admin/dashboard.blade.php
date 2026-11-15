@extends('layouts.super-admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Platform overview & real-time insights')

@section('content')
    {{-- Stat cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-6 mb-6 sm:mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 sm:p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Total Tenants</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($totalTenants) }}</p>
                    <p class="mt-1 text-xs text-emerald-600 font-medium">{{ $activeTenants }} active</p>
                </div>
                <div class="p-3 rounded-xl bg-brand-50 text-brand-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 sm:p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Active Users</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($activeUsers) }}</p>
                    <p class="mt-1 text-xs text-slate-500">Across all tenants</p>
                </div>
                <div class="p-3 rounded-xl bg-violet-50 text-violet-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 sm:p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">News Posts</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($totalPosts) }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $publishedPosts }} published</p>
                </div>
                <div class="p-3 rounded-xl bg-amber-50 text-amber-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 sm:p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Monthly Revenue</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">${{ number_format($monthlyRevenue, 0) }}</p>
                    <p class="mt-1 text-xs text-slate-500">From subscriptions</p>
                </div>
                <div class="p-3 rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 sm:gap-6 mb-6 sm:mb-8">
        {{-- Revenue chart --}}
        <div class="xl:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200 p-5 sm:p-6">
            <h2 class="text-base font-semibold text-slate-900 mb-1">Revenue Statistics</h2>
            <p class="text-sm text-slate-500 mb-6">Last 6 months subscription revenue</p>
            @php $maxRevenue = max(collect($revenueByMonth)->max('amount'), 1); @endphp
            <div class="flex items-end justify-between gap-2 sm:gap-4 h-48">
                @foreach ($revenueByMonth as $month)
                    <div class="flex-1 flex flex-col items-center gap-2 min-w-0">
                        <span class="text-xs font-medium text-slate-600">${{ number_format($month['amount'], 0) }}</span>
                        <div class="w-full max-w-[48px] bg-slate-100 rounded-t-lg flex items-end" style="height: 140px;">
                            <div class="w-full bg-brand-600 rounded-t-lg transition-all" style="height: {{ max(($month['amount'] / $maxRevenue) * 100, 8) }}%"></div>
                        </div>
                        <span class="text-xs text-slate-500 font-medium">{{ $month['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Live visitors + tenant status --}}
        <div class="space-y-4 sm:space-y-6">
            <div class="bg-gradient-to-br from-brand-700 to-brand-900 rounded-xl shadow-sm p-5 sm:p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-semibold">Live Visitors</h2>
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                </div>
                <p class="text-4xl font-bold">{{ number_format($liveVisitors) }}</p>
                <p class="text-sm text-brand-100 mt-2">Active sessions in the last 30 minutes</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 sm:p-6">
                <h2 class="text-base font-semibold text-slate-900 mb-4">Tenant Status</h2>
                <div class="space-y-3">
                    @foreach ($tenantStatusBreakdown as $item)
                        @php
                            $barColors = ['emerald' => '#10b981', 'amber' => '#f59e0b', 'rose' => '#f43f5e', 'sky' => '#0ea5e9'];
                            $pct = $totalTenants > 0 ? ($item['count'] / $totalTenants) * 100 : 0;
                        @endphp
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-medium text-slate-700">{{ $item['label'] }}</span>
                                <span class="text-slate-500">{{ $item['count'] }}</span>
                            </div>
                            <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full" style="width: {{ $pct }}%; background-color: {{ $barColors[$item['color']] ?? '#64748b' }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        {{-- Server usage --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 sm:p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Server Usage</h2>
                    <p class="text-sm text-slate-500">System resource utilization</p>
                </div>
                <span class="text-xs font-medium text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full">Uptime {{ $serverUsage['uptime'] }}</span>
            </div>
            @foreach ([
                ['label' => 'CPU', 'value' => $serverUsage['cpu'], 'color' => 'bg-brand-600'],
                ['label' => 'Memory', 'value' => $serverUsage['memory'], 'color' => 'bg-violet-600'],
                ['label' => 'Disk', 'value' => $serverUsage['disk'], 'color' => 'bg-amber-500'],
            ] as $metric)
                <div class="mb-4 last:mb-0">
                    <div class="flex justify-between text-sm mb-1.5">
                        <span class="font-medium text-slate-700">{{ $metric['label'] }}</span>
                        <span class="text-slate-600">{{ $metric['value'] }}%</span>
                    </div>
                    <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="{{ $metric['color'] }} h-full rounded-full transition-all" style="width: {{ min($metric['value'], 100) }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Recent activities --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 sm:p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Recent Activities</h2>
                    <p class="text-sm text-slate-500">Latest platform events</p>
                </div>
                <a href="{{ route('super-admin.tenants.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">View tenants</a>
            </div>
            <div class="space-y-4 max-h-80 overflow-y-auto pr-1">
                @forelse ($recentActivities as $activity)
                    <div class="flex gap-3">
                        <div class="w-2 h-2 mt-2 rounded-full bg-brand-500 shrink-0"></div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-900">{{ $activity->title }}</p>
                            @if ($activity->description)
                                <p class="text-sm text-slate-500 truncate">{{ $activity->description }}</p>
                            @endif
                            <p class="text-xs text-slate-400 mt-0.5">{{ $activity->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 text-center py-8">No recent activity yet.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
