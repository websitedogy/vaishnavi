@extends('layouts.super-admin')

@section('title', 'Maintenance Mode')
@section('page-title', 'Maintenance Mode')
@section('page-subtitle', 'Temporary platform shutdown control')

@section('content')
<div class="max-w-2xl bg-white rounded-xl border border-slate-200 p-6">
    <div class="mb-5">
        <p class="text-sm text-slate-500">Current status</p>
        <p class="text-xl font-semibold {{ $enabled ? 'text-rose-600' : 'text-emerald-600' }}">
            {{ $enabled ? 'Maintenance Enabled' : 'Platform Live' }}
        </p>
    </div>

    <form method="POST" action="{{ route('super-admin.maintenance.update') }}" class="space-y-4">
        @csrf
        @method('PUT')
        <label class="flex items-center gap-2">
            <input type="checkbox" name="enabled" value="1" class="rounded text-brand-600" @checked($enabled)>
            <span class="text-sm font-medium text-slate-700">Enable maintenance mode</span>
        </label>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Maintenance message</label>
            <textarea name="message" rows="4" class="w-full rounded-lg border-slate-300 text-sm">{{ $message }}</textarea>
        </div>
        <button class="px-5 py-2.5 bg-brand-600 text-white rounded-lg text-sm font-semibold">Save</button>
    </form>
</div>
@endsection
