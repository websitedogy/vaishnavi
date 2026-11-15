@extends('layouts.super-admin')

@section('title', 'API Management')
@section('page-title', 'API Management')
@section('page-subtitle', 'Generate/manage API keys and inspect usage logs')

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-900 mb-4">Generate API Key</h2>
        <form method="POST" action="{{ route('super-admin.api.keys.store') }}" class="space-y-3">
            @csrf
            <input type="text" name="name" placeholder="Key name" class="w-full rounded-lg border-slate-300 text-sm" required>
            <select name="tenant_id" class="w-full rounded-lg border-slate-300 text-sm">
                <option value="">Global key</option>
                @foreach ($tenants as $tenant)
                    <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                @endforeach
            </select>
            <input type="date" name="expires_at" class="w-full rounded-lg border-slate-300 text-sm">
            <button class="w-full py-2.5 bg-brand-600 text-white rounded-lg text-sm font-semibold">Generate Key</button>
        </form>
    </div>
    <div class="xl:col-span-2 bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-900 mb-4">API Keys</h2>
        <div class="space-y-2 max-h-72 overflow-y-auto">
            @forelse ($apiKeys as $key)
                <div class="p-3 rounded bg-slate-50">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-slate-800">{{ $key->name }}</p>
                            <p class="text-xs text-slate-500">{{ $key->tenant?->name ?? 'Global' }} · {{ $key->key }}</p>
                        </div>
                        <form method="POST" action="{{ route('super-admin.api.keys.toggle', $key) }}">
                            @csrf
                            <button class="text-xs font-semibold {{ $key->is_active ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $key->is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">No API keys available.</p>
            @endforelse
        </div>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 p-5">
    <h2 class="font-semibold text-slate-900 mb-4">API Usage Logs</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-2 text-left">Endpoint</th>
                    <th class="px-4 py-2 text-left">Method</th>
                    <th class="px-4 py-2 text-left">Tenant</th>
                    <th class="px-4 py-2 text-left">Status</th>
                    <th class="px-4 py-2 text-left">Response</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse ($usageLogs as $log)
                    <tr>
                        <td class="px-4 py-2">{{ $log->endpoint }}</td>
                        <td class="px-4 py-2">{{ $log->method }}</td>
                        <td class="px-4 py-2">{{ $log->tenant?->name ?? 'Global' }}</td>
                        <td class="px-4 py-2">{{ $log->status_code }}</td>
                        <td class="px-4 py-2">{{ $log->response_time_ms }} ms</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No usage logs yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
