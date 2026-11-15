@extends('layouts.tenant-admin')
@section('page-title','Author profile')
@section('content')
<div class="grid md:grid-cols-3 gap-6">
<div class="bg-white rounded-xl border p-6"><h2 class="text-xl font-bold">{{ $staff->name }}</h2><p class="text-slate-500">{{ $staff->email }}</p><p class="mt-2 text-sm">{{ $staff->role->label() }}</p>
<div class="mt-4 grid grid-cols-3 gap-2 text-center text-sm"><div><p class="font-bold text-lg">{{ $stats['articles'] }}</p><p class="text-slate-500">Articles</p></div><div><p class="font-bold text-lg">{{ $stats['published'] }}</p><p class="text-slate-500">Published</p></div><div><p class="font-bold text-lg">{{ number_format($stats['views']) }}</p><p class="text-slate-500">Views</p></div></div></div>
<div class="md:col-span-2 space-y-4">
<div class="bg-white rounded-xl border p-5"><h3 class="font-semibold mb-3">Recent articles</h3><ul class="text-sm space-y-2">@forelse($recent as $a)<li><a href="{{ route('tenant-admin.news.edit',$a) }}" class="text-brand-600">{{ $a->title }}</a></li>@empty<li class="text-slate-500">No articles yet.</li>@endforelse</ul></div>
<div class="bg-white rounded-xl border p-5"><h3 class="font-semibold mb-3">Activity</h3><ul class="text-sm space-y-1">@foreach($activity as $log)<li class="text-slate-600">{{ $log->created_at->diffForHumans() }} — {{ $log->description }}</li>@endforeach</ul></div>
</div></div>
@endsection
