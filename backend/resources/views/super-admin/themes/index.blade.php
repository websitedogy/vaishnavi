@extends('layouts.super-admin')
@section('title', 'Theme Management')
@section('page-title', 'Global Theme Management')
@section('page-subtitle', 'Assign themes, layouts, colors & features per tenant')

@section('content')
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border p-4"><p class="text-sm text-slate-500">Themed Tenants</p><p class="text-2xl font-bold">{{ $globalStats['themed'] }}</p></div>
    <div class="bg-white rounded-xl border p-4"><p class="text-sm text-slate-500">Live TV Enabled</p><p class="text-2xl font-bold">{{ $globalStats['live_tv'] }}</p></div>
    <div class="bg-white rounded-xl border p-4"><p class="text-sm text-slate-500">Polls Enabled</p><p class="text-2xl font-bold">{{ $globalStats['polls'] }}</p></div>
    <div class="bg-white rounded-xl border p-4"><p class="text-sm text-slate-500">Theme Presets</p><p class="text-2xl font-bold">{{ count($presets) }}</p></div>
</div>

<form method="GET" class="mb-6"><input type="search" name="search" value="{{ request('search') }}" placeholder="Search tenant..." class="w-full sm:max-w-md rounded-lg border-slate-300 text-sm"></form>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
@foreach($tenants as $tenant)
@php $theme = $tenant->theme; $features = $theme?->features ?? \App\Models\TenantTheme::defaultFeatures(); @endphp
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-md transition">
    <div class="h-3 flex" style="background: linear-gradient(90deg, {{ $theme->primary_color ?? '#2563eb' }}, {{ $theme->accent_color ?? '#f59e0b' }})"></div>
    <div class="p-5">
        <div class="flex justify-between items-start mb-3">
            <h3 class="font-semibold text-slate-900">{{ $tenant->name }}</h3>
            <span class="text-xs px-2 py-1 rounded bg-slate-100 capitalize">{{ $theme->theme_preset ?? 'classic' }}</span>
        </div>
        <p class="text-sm text-slate-500 mb-3">Layout: <strong class="text-slate-700">{{ $theme->layout ?? 'grid' }}</strong> · Font: {{ $theme->font_family ?? 'Inter' }}</p>
        <div class="flex flex-wrap gap-1 mb-4">
            @foreach(config('themes.features') as $key => $label)
            <span class="text-xs px-2 py-0.5 rounded {{ ($features[$key] ?? false) ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400' }}">{{ $label }}</span>
            @endforeach
        </div>
        <a href="{{ route('super-admin.themes.edit', $tenant) }}" class="inline-flex items-center text-sm font-medium text-brand-600 hover:text-brand-700">Configure theme →</a>
    </div>
</div>
@endforeach
</div>
@if($tenants->hasPages())<div class="mt-6">{{ $tenants->links() }}</div>@endif
@endsection
