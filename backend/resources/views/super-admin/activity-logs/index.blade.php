@extends('layouts.super-admin')

@section('title', 'Activity Logs')
@section('page-title', 'Activity Logs')
@section('page-subtitle', 'Track login history, tenant/news/payment actions')

@section('content')
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
    <form method="GET" class="flex flex-wrap gap-3">
        <select name="type" class="rounded-lg border-slate-300 text-sm">
            <option value="">All events</option>
            @foreach ($types as $type)
                <option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>
            @endforeach
        </select>
        <button class="px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-semibold">Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50 text-xs uppercase text-slate-600">
                <tr>
                    <th class="px-4 py-3 text-left">Event</th>
                    <th class="px-4 py-3 text-left">Type</th>
                    <th class="px-4 py-3 text-left">Tenant</th>
                    <th class="px-4 py-3 text-left">Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 text-sm">
                @forelse ($logs as $log)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-800">{{ $log->title }}</p>
                            <p class="text-xs text-slate-500">{{ $log->description }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $log->type }}</td>
                        <td class="px-4 py-3">{{ $log->tenant?->name ?? 'Global' }}</td>
                        <td class="px-4 py-3">{{ $log->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-slate-500">No activity logs found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($logs->hasPages())
    <div class="mt-4">{{ $logs->links() }}</div>
@endif
@endsection
