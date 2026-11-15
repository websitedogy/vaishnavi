@extends('layouts.super-admin')

@section('title', 'Storage Management')
@section('page-title', 'Storage Management')
@section('page-subtitle', 'Control image/video limits and monitor disk usage')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-sm text-slate-500">Total Disk Usage</p>
        <p class="text-3xl font-bold text-slate-900 mt-1">{{ $usage['used_gb'] }} GB</p>
        <p class="text-xs text-slate-500 mt-1">of {{ $usage['total_gb'] }} GB</p>
        <div class="h-2 bg-slate-100 rounded-full mt-3">
            <div class="h-2 bg-brand-600 rounded-full" style="width: {{ min($usage['percent'], 100) }}%"></div>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-sm text-slate-500">Image Upload Limit</p>
        <p class="text-3xl font-bold text-slate-900 mt-1">{{ $settings['image_upload_size_mb'] }} MB</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-sm text-slate-500">Video Storage Limit</p>
        <p class="text-3xl font-bold text-slate-900 mt-1">{{ $settings['video_storage_limit_gb'] }} GB</p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-900 mb-4">Control Limits</h2>
        <form method="POST" action="{{ route('super-admin.storage.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Image upload size (MB)</label>
                <input type="number" name="image_upload_size_mb" min="1" value="{{ $settings['image_upload_size_mb'] }}" class="w-full rounded-lg border-slate-300 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Video storage (GB)</label>
                <input type="number" name="video_storage_limit_gb" min="1" value="{{ $settings['video_storage_limit_gb'] }}" class="w-full rounded-lg border-slate-300 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tenant disk quota (GB)</label>
                <input type="number" name="tenant_disk_limit_gb" min="1" value="{{ $settings['tenant_disk_limit_gb'] }}" class="w-full rounded-lg border-slate-300 text-sm">
            </div>
            <button class="w-full py-2.5 bg-brand-600 text-white rounded-lg text-sm font-semibold">Save Limits</button>
        </form>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-900 mb-4">Tenant Storage Usage (Estimated)</h2>
        <div class="space-y-2 max-h-80 overflow-y-auto">
            @forelse ($tenantUsage as $row)
                <div class="flex items-center justify-between p-2 rounded bg-slate-50">
                    <div>
                        <p class="text-sm font-medium text-slate-800">{{ $row['name'] }}</p>
                        <p class="text-xs text-slate-500">{{ $row['posts'] }} posts</p>
                    </div>
                    <span class="text-sm font-semibold text-slate-700">{{ $row['estimated_gb'] }} GB</span>
                </div>
            @empty
                <p class="text-sm text-slate-500">No tenant data found.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
