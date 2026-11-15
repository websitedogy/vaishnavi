@extends('layouts.tenant-admin')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Real-time overview')
@section('header-actions')
    <a href="{{ route('tenant-admin.news.create') }}" class="inline-flex items-center px-3 py-2 bg-brand-600 text-white text-sm font-semibold rounded-lg hover:bg-brand-700">+ New article</a>
@endsection
@section('content')
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5"><p class="text-sm text-slate-500">Total articles</p><p class="text-3xl font-bold mt-1">{{ $stats['total_articles'] }}</p></div>
    <div class="bg-white rounded-xl border border-slate-200 p-5"><p class="text-sm text-slate-500">Today visitors</p><p class="text-3xl font-bold mt-1 text-sky-600">{{ number_format($stats['today_visitors']) }}</p></div>
    <div class="bg-white rounded-xl border border-slate-200 p-5"><p class="text-sm text-slate-500">Revenue overview</p><p class="text-3xl font-bold mt-1 text-emerald-600">${{ number_format($stats['revenue'], 2) }}</p></div>
    <div class="bg-white rounded-xl border border-slate-200 p-5"><p class="text-sm text-slate-500">Pending approvals</p><p class="text-3xl font-bold mt-1 text-amber-600">{{ $stats['pending_approvals'] }}</p></div>
</div>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-900 mb-4">Real-time analytics (7 days)</h2>
        <canvas id="analyticsChart" height="120"></canvas>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-900 mb-3">Breaking news</h2>
        @if($stats['breaking_active'])<span class="inline-flex px-3 py-1 rounded-full bg-rose-100 text-rose-800 text-sm font-medium">LIVE breaking story active</span>
        @else<span class="text-sm text-slate-500">No active breaking tag</span>@endif
        <h2 class="font-semibold text-slate-900 mt-6 mb-3">Quick publish</h2>
        <div class="space-y-2">
            <a href="{{ route('tenant-admin.news.create') }}" class="block w-full text-center py-2 rounded-lg bg-brand-600 text-white text-sm font-medium">New article</a>
            <a href="{{ route('tenant-admin.news.index', ['status' => 'draft']) }}" class="block w-full text-center py-2 rounded-lg border border-slate-200 text-sm">View drafts ({{ $stats['drafts'] }})</a>
            <a href="{{ route('tenant-admin.media.index') }}" class="block w-full text-center py-2 rounded-lg border border-slate-200 text-sm">Upload media</a>
            <a href="{{ auth('tenant_admin')->user()->tenant->publicSiteUrl() }}" target="_blank" class="block w-full text-center py-2 rounded-lg border border-violet-300 text-violet-700 text-sm font-medium bg-violet-50">View public site ↗</a>
        </div>
    </div>
</div>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold mb-3">Trending news</h2>
        <ul class="divide-y divide-slate-100">@forelse($trending as $t)<li class="py-2 flex justify-between text-sm"><a href="{{ route('tenant-admin.news.edit', $t) }}" class="text-brand-600 hover:underline truncate pr-2">{{ $t->title }}</a><span class="text-slate-500 shrink-0">{{ number_format($t->views) }} views</span></li>@empty<li class="text-slate-500 text-sm">No published articles yet.</li>@endforelse</ul>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold mb-3">Latest comments</h2>
        <ul class="space-y-3">@forelse($latestComments as $c)<li class="text-sm border-b border-slate-50 pb-2"><strong>{{ $c->author_name }}</strong> on {{ $c->post?->title }}<p class="text-slate-600 truncate">{{ $c->body }}</p>@unless($c->is_approved)<form method="POST" action="{{ route('tenant-admin.comments.approve', $c) }}" class="mt-1">@csrf<button class="text-xs text-brand-600">Approve</button></form>@endunless</li>@empty<li class="text-slate-500 text-sm">No comments yet.</li>@endforelse</ul>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold mb-3">Pending approvals</h2>
        <ul class="space-y-2">@forelse($pendingApprovals as $p)<li class="flex justify-between items-center text-sm"><span>{{ $p->title }} <span class="text-slate-400">— {{ $p->author?->name }}</span></span><form method="POST" action="{{ route('tenant-admin.news.approve', $p) }}">@csrf<button class="text-brand-600 text-xs font-medium">Approve</button></form></li>@empty<li class="text-slate-500 text-sm">All clear.</li>@endforelse</ul>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold mb-3">Author activity</h2>
        <ul class="space-y-2 text-sm">@forelse($authorActivity as $log)<li><span class="text-slate-500">{{ $log->created_at->diffForHumans() }}</span> — <strong>{{ $log->user?->name ?? 'System' }}</strong>: {{ $log->description }}</li>@empty<li class="text-slate-500">No activity logged yet.</li>@endforelse</ul>
        <a href="{{ route('tenant-admin.staff.logs') }}" class="inline-block mt-3 text-sm text-brand-600">View all logs →</a>
    </div>
</div>
@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
const ctx=document.getElementById('analyticsChart');
new Chart(ctx,{type:'line',data:{labels:@json($chart['labels']),datasets:[{label:'Page views',data:@json($chart['views']),borderColor:'#2563eb',tension:.3},{label:'Visitors',data:@json($chart['visitors']),borderColor:'#059669',tension:.3}]},options:{responsive:true,plugins:{legend:{position:'bottom'}}}});
setInterval(()=>fetch('{{ route('tenant-admin.analytics.live') }}').then(r=>r.json()).then(d=>{Chart.getChart(ctx).data.labels=d.labels;Chart.getChart(ctx).data.datasets[0].data=d.views;Chart.getChart(ctx).data.datasets[1].data=d.visitors;Chart.getChart(ctx).update();}),60000);
</script>
@endpush
