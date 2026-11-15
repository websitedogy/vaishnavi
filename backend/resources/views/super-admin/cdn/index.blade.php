@extends('layouts.super-admin')

@section('title', 'CDN & Cache Controls')
@section('page-title', 'CDN & Cache Controls')
@section('page-subtitle', 'Cloudflare integration and cache operations')

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <form method="POST" action="{{ route('super-admin.cdn.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Provider</label>
                <select name="provider" class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="cloudflare" @selected($settings['provider'] === 'cloudflare')>Cloudflare</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Cloudflare Zone ID</label>
                <input type="text" name="zone_id" value="{{ $settings['zone_id'] }}" class="w-full rounded-lg border-slate-300 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Cloudflare API Token</label>
                <input type="text" name="api_token" value="{{ $settings['api_token'] }}" class="w-full rounded-lg border-slate-300 text-sm">
            </div>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="cache_enabled" value="1" class="rounded text-brand-600" @checked($settings['cache_enabled'])>
                <span class="text-sm text-slate-700">Enable cache</span>
            </label>
            <button class="px-5 py-2.5 bg-brand-600 text-white rounded-lg text-sm font-semibold">Save CDN Settings</button>
        </form>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-900 mb-3">Cache Actions</h2>
        <p class="text-sm text-slate-500 mb-4">Use Cloudflare purge + app cache clear.</p>
        <form method="POST" action="{{ route('super-admin.cdn.purge') }}">
            @csrf
            <button class="px-5 py-2.5 bg-slate-900 text-white rounded-lg text-sm font-semibold">Purge Cache</button>
        </form>
        <p class="text-xs text-slate-500 mt-4">Last purged: {{ $settings['last_purged_at'] ?: 'Never' }}</p>
    </div>
</div>
@endsection
