@extends('layouts.super-admin')
@section('title', 'Advertisements')
@section('page-title', 'Global Advertisement Management')
@section('page-subtitle', 'Header, sidebar, footer, popup ads & revenue tracking')

@section('header-actions')
<a href="{{ route('super-admin.advertisements.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 text-white text-sm font-semibold rounded-lg hover:bg-brand-700">Add Ad</a>
@endsection

@section('content')
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border p-5 shadow-sm">
        <p class="text-sm text-slate-500">Total Revenue</p>
        <p class="text-2xl font-bold text-emerald-600">${{ number_format($revenueStats['total_revenue'], 2) }}</p>
    </div>
    <div class="bg-white rounded-xl border p-5 shadow-sm">
        <p class="text-sm text-slate-500">Impressions</p>
        <p class="text-2xl font-bold">{{ number_format($revenueStats['total_impressions']) }}</p>
    </div>
    <div class="bg-white rounded-xl border p-5 shadow-sm">
        <p class="text-sm text-slate-500">Clicks</p>
        <p class="text-2xl font-bold">{{ number_format($revenueStats['total_clicks']) }}</p>
    </div>
    <div class="bg-white rounded-xl border p-5 shadow-sm">
        <p class="text-sm text-slate-500">CTR</p>
        <p class="text-2xl font-bold">{{ $revenueStats['total_impressions'] > 0 ? number_format(($revenueStats['total_clicks'] / $revenueStats['total_impressions']) * 100, 2) : 0 }}%</p>
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    @foreach($revenueStats['by_placement'] as $item)
    <div class="bg-slate-800 text-white rounded-lg p-4">
        <p class="text-xs text-slate-400">{{ $item['label'] }}</p>
        <p class="text-lg font-bold">${{ number_format($item['revenue'], 0) }}</p>
        <p class="text-xs text-slate-400 mt-1">{{ $item['count'] }} ads</p>
    </div>
    @endforeach
</div>

<form method="GET" class="flex flex-wrap gap-3 mb-6">
    <select name="placement" class="rounded-lg border-slate-300 text-sm">
        <option value="">All placements</option>
        @foreach(\App\Enums\AdPlacement::cases() as $p)<option value="{{ $p->value }}" @selected(request('placement') === $p->value)>{{ $p->label() }}</option>@endforeach
    </select>
    <select name="tenant_id" class="rounded-lg border-slate-300 text-sm">
        <option value="">All scopes</option>
        <option value="global" @selected(request('scope') === 'global')>Global only</option>
        @foreach($tenants as $t)<option value="{{ $t->id }}" @selected(request('tenant_id') == $t->id)>{{ $t->name }}</option>@endforeach
    </select>
    <button type="submit" class="px-4 py-2 bg-white border rounded-lg text-sm font-medium">Filter</button>
</form>

<div class="bg-white rounded-xl border shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50"><tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Ad</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600 hidden sm:table-cell">Placement</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600 hidden md:table-cell">Scope</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Revenue</th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600">Actions</th>
        </tr></thead>
        <tbody class="divide-y divide-slate-200">
        @forelse($ads as $ad)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-4">
                <p class="font-medium text-slate-900">{{ $ad->name }}</p>
                <p class="text-xs text-slate-500">{{ $ad->advertiser ?? '—' }} · {{ $ad->is_active ? 'Active' : 'Inactive' }}</p>
            </td>
            <td class="px-4 py-4 hidden sm:table-cell"><span class="text-xs font-medium px-2 py-1 rounded bg-violet-100 text-violet-800">{{ $ad->placement->label() }}</span></td>
            <td class="px-4 py-4 hidden md:table-cell text-sm">{{ $ad->tenant?->name ?? 'Global' }}</td>
            <td class="px-4 py-4 text-sm">
                <span class="font-semibold text-emerald-600">${{ number_format($ad->revenue, 2) }}</span>
                <span class="block text-xs text-slate-400">{{ number_format($ad->impressions) }} imp · {{ $ad->ctr() }}% CTR</span>
            </td>
            <td class="px-4 py-4 text-right whitespace-nowrap">
                <a href="{{ route('super-admin.advertisements.edit', $ad) }}" class="text-sm text-brand-600 font-medium">Edit</a>
                <form method="POST" action="{{ route('super-admin.advertisements.destroy', $ad) }}" class="inline" onsubmit="return confirm('Delete ad?')">@csrf @method('DELETE')
                <button class="text-sm text-rose-600 font-medium ml-2">Delete</button></form>
            </td>
        </tr>
        @empty
        <tr><td colspan="5" class="px-4 py-12 text-center text-slate-500">No advertisements yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    @if($ads->hasPages())<div class="px-4 py-3 border-t">{{ $ads->links() }}</div>@endif
</div>
@endsection
