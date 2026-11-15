@extends('layouts.super-admin')

@section('title', 'Audit System')
@section('page-title', 'Audit System')
@section('page-subtitle', 'Complete monitoring: who changed what and when')

@section('content')
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
    <form method="GET" class="flex gap-3 flex-wrap">
        <select name="action" class="rounded-lg border-slate-300 text-sm">
            <option value="">All actions</option>
            @foreach ($actions as $action)
                <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
            @endforeach
        </select>
        <button class="px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-semibold">Filter</button>
    </form>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b"><h2 class="font-semibold text-slate-900">Audit Trail</h2></div>
        <div class="max-h-[520px] overflow-y-auto">
            @forelse ($auditLogs as $log)
                <div class="p-4 border-b last:border-b-0">
                    <p class="font-medium text-slate-800">{{ $log->action }}</p>
                    <p class="text-sm text-slate-600">{{ $log->summary }}</p>
                    <p class="text-xs text-slate-500 mt-1">
                        {{ $log->superAdmin?->name ?? 'System' }} · {{ $log->performed_at?->diffForHumans() }} · {{ $log->ip_address }}
                    </p>
                </div>
            @empty
                <p class="p-5 text-sm text-slate-500">No audit logs found.</p>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b"><h2 class="font-semibold text-slate-900">Login History</h2></div>
        <div class="max-h-[520px] overflow-y-auto">
            @forelse ($loginLogs as $log)
                <div class="p-4 border-b last:border-b-0">
                    <p class="text-sm font-medium {{ $log->is_success ? 'text-emerald-600' : 'text-rose-600' }}">
                        {{ $log->is_success ? 'Successful Login' : 'Failed Attempt' }}
                    </p>
                    <p class="text-sm text-slate-700">{{ $log->email }}</p>
                    <p class="text-xs text-slate-500 mt-1">{{ $log->attempted_at?->diffForHumans() }} · {{ $log->ip_address }}</p>
                </div>
            @empty
                <p class="p-5 text-sm text-slate-500">No login history found.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
