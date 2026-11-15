@extends('layouts.super-admin')

@section('title', 'Security Management')
@section('page-title', 'Security Management')
@section('page-subtitle', 'IP blocking, login logs, failed attempts, and sessions')

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-sm text-slate-500">Failed login attempts (7 days)</p>
        <p class="text-3xl font-bold text-rose-600 mt-1">{{ number_format($failedAttempts) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-sm text-slate-500">Blocked IP addresses</p>
        <p class="text-3xl font-bold text-slate-900 mt-1">{{ number_format($blockedIps->total()) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-sm text-slate-500">Active sessions</p>
        <p class="text-3xl font-bold text-slate-900 mt-1">{{ number_format($activeSessions->total()) }}</p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-900 mb-4">IP Blocking</h2>
        <form method="POST" action="{{ route('super-admin.security.block-ip') }}" class="grid sm:grid-cols-3 gap-3 mb-4">
            @csrf
            <input type="text" name="ip_address" placeholder="IP address" class="rounded-lg border-slate-300 text-sm" required>
            <input type="text" name="reason" placeholder="Reason (optional)" class="rounded-lg border-slate-300 text-sm">
            <button type="submit" class="bg-rose-600 text-white rounded-lg px-4 py-2 text-sm font-semibold">Block IP</button>
        </form>
        <div class="space-y-2 max-h-56 overflow-y-auto">
            @forelse ($blockedIps as $ip)
                <div class="flex items-center justify-between p-2 rounded bg-slate-50">
                    <div>
                        <p class="font-medium text-slate-800">{{ $ip->ip_address }}</p>
                        <p class="text-xs text-slate-500">{{ $ip->reason ?: 'No reason' }}</p>
                    </div>
                    <form method="POST" action="{{ route('super-admin.security.unblock-ip', $ip) }}">
                        @csrf @method('DELETE')
                        <button class="text-xs text-emerald-600 font-semibold">Unblock</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-slate-500">No blocked IPs.</p>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-900 mb-4">Tenant Locking</h2>
        <p class="text-sm text-slate-500 mb-4">Permission restrictions can be applied from User Management. Use quick lock below to suspend a tenant.</p>
        <div class="space-y-2 max-h-64 overflow-y-auto">
            @foreach (\App\Models\Tenant::orderBy('name')->limit(12)->get() as $tenant)
                <div class="flex items-center justify-between p-2 rounded bg-slate-50">
                    <div>
                        <p class="font-medium text-slate-800">{{ $tenant->name }}</p>
                        <p class="text-xs text-slate-500">{{ $tenant->status->value }}</p>
                    </div>
                    <form method="POST" action="{{ route('super-admin.security.lock-tenant', $tenant) }}">
                        @csrf
                        <button class="text-xs text-amber-600 font-semibold">Lock</button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-900 mb-4">Login Logs</h2>
        <div class="space-y-2 max-h-72 overflow-y-auto">
            @forelse ($loginLogs as $log)
                <div class="p-2 rounded bg-slate-50">
                    <div class="flex justify-between text-xs">
                        <span class="{{ $log->is_success ? 'text-emerald-600' : 'text-rose-600' }}">{{ $log->is_success ? 'Success' : 'Failed' }}</span>
                        <span class="text-slate-500">{{ $log->attempted_at?->diffForHumans() }}</span>
                    </div>
                    <p class="text-sm text-slate-800">{{ $log->email ?: 'Unknown email' }}</p>
                    <p class="text-xs text-slate-500">{{ $log->ip_address }} · {{ $log->guard }}</p>
                </div>
            @empty
                <p class="text-sm text-slate-500">No logs available.</p>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-900 mb-4">Session Management</h2>
        <div class="space-y-2 max-h-72 overflow-y-auto">
            @forelse ($activeSessions as $session)
                <div class="flex items-center justify-between p-2 rounded bg-slate-50">
                    <div>
                        <p class="text-sm font-medium text-slate-800">{{ $session->user?->name }}</p>
                        <p class="text-xs text-slate-500">{{ $session->ip_address }} · {{ $session->last_seen_at?->diffForHumans() }}</p>
                    </div>
                    <form method="POST" action="{{ route('super-admin.security.revoke-session', $session) }}">
                        @csrf
                        <button class="text-xs text-rose-600 font-semibold">Revoke</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-slate-500">No active sessions.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
