@extends('layouts.super-admin')

@section('title', 'Database Management')
@section('page-title', 'Database Management')
@section('page-subtitle', 'Cleanup, optimization, and database logs tools')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-500">Database</p><p class="text-lg font-semibold">{{ $stats['database'] }}</p></div>
    <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-500">Tables</p><p class="text-lg font-semibold">{{ $stats['tables'] }}</p></div>
    <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-500">Rows</p><p class="text-lg font-semibold">{{ number_format($stats['rows']) }}</p></div>
    <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-500">Size</p><p class="text-lg font-semibold">{{ $stats['size_mb'] }} MB</p></div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-900 mb-3">Cleanup</h2>
        <p class="text-sm text-slate-500 mb-4">Deletes old logs and stale closed support tickets.</p>
        <form method="POST" action="{{ route('super-admin.database.cleanup') }}">
            @csrf
            <button class="px-5 py-2.5 bg-amber-500 text-white rounded-lg text-sm font-semibold">Run Cleanup</button>
        </form>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-900 mb-3">Optimization</h2>
        <p class="text-sm text-slate-500 mb-4">Runs table optimization for key logs tables.</p>
        <form method="POST" action="{{ route('super-admin.database.optimize') }}">
            @csrf
            <button class="px-5 py-2.5 bg-emerald-600 text-white rounded-lg text-sm font-semibold">Optimize Tables</button>
        </form>
    </div>
</div>
@endsection
