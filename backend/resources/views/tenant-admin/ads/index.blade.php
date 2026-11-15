@extends('layouts.tenant-admin')
@section('page-title','Advertisement Management')
@section('header-actions')<a href="{{ route('tenant-admin.ads.create') }}" class="px-3 py-2 bg-brand-600 text-white text-sm font-semibold rounded-lg">+ Add ad</a>@endsection
@section('content')
<div class="grid sm:grid-cols-3 gap-4 mb-6">
<div class="bg-white rounded-xl border p-4"><p class="text-sm text-slate-500">Total clicks</p><p class="text-2xl font-bold">{{ number_format($totalClicks) }}</p></div>
<div class="bg-white rounded-xl border p-4"><p class="text-sm text-slate-500">Impressions</p><p class="text-2xl font-bold">{{ number_format($totalImpressions) }}</p></div>
<div class="bg-white rounded-xl border p-4"><p class="text-sm text-slate-500">CTR avg</p><p class="text-2xl font-bold">{{ $totalImpressions ? round(($totalClicks/$totalImpressions)*100,2) : 0 }}%</p></div>
</div>
<div class="bg-white rounded-xl border p-5 mb-6"><h2 class="font-semibold mb-3">Google AdSense</h2>
<form method="POST" action="{{ route('tenant-admin.ads.adsense') }}" class="space-y-3">@csrf
<input name="adsense_publisher_id" value="{{ $adsConfig['adsense_publisher_id'] ?? '' }}" placeholder="ca-pub-XXXXXXXX" class="w-full rounded-lg border-slate-300 text-sm">
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="adsense_auto_ads" value="1" @checked($adsConfig['adsense_auto_ads'] ?? false)> Enable auto ads</label>
<button class="px-4 py-2 bg-brand-600 text-white text-sm rounded-lg">Save AdSense</button>
</form></div>
<div class="bg-white rounded-xl border overflow-hidden">
<table class="w-full text-sm"><thead class="bg-slate-50"><tr><th class="p-3 text-left">Name</th><th class="p-3">Type</th><th class="p-3">Placement</th><th class="p-3">Clicks</th><th class="p-3">Schedule</th><th class="p-3"></th></tr></thead>
<tbody>@foreach($ads as $ad)<tr class="border-t"><td class="p-3 font-medium">{{ $ad->name }}</td><td class="p-3">{{ $ad->ad_type }}</td><td class="p-3">{{ $ad->placement?->label() ?? $ad->placement }}</td><td class="p-3">{{ $ad->clicks }} / {{ $ad->impressions }}</td><td class="p-3 text-xs">{{ $ad->starts_at?->format('M d') ?? '—' }} – {{ $ad->ends_at?->format('M d') ?? '∞' }}</td>
<td class="p-3 text-right"><a href="{{ route('tenant-admin.ads.edit',$ad) }}" class="text-brand-600">Edit</a></td></tr>@endforeach</tbody></table>
</div>
<div class="mt-4">{{ $ads->links() }}</div>
@endsection
