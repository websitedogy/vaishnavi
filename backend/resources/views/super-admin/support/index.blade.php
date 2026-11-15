@extends('layouts.super-admin')

@section('title', 'Support System')
@section('page-title', 'Support System')
@section('page-subtitle', 'Tenant support tickets and replies')

@section('content')
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
    <form method="GET" class="flex flex-wrap gap-3">
        <select name="status" class="rounded-lg border-slate-300 text-sm">
            <option value="">All statuses</option>
            @foreach (['open', 'in_progress', 'resolved', 'closed'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
            @endforeach
        </select>
        <button class="px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-semibold">Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50 text-xs uppercase text-slate-600">
            <tr>
                <th class="px-4 py-3 text-left">Ticket</th>
                <th class="px-4 py-3 text-left">Tenant</th>
                <th class="px-4 py-3 text-left">Priority</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-right">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 text-sm">
            @forelse ($tickets as $ticket)
                <tr>
                    <td class="px-4 py-3">
                        <p class="font-medium text-slate-800">{{ $ticket->subject }}</p>
                        <p class="text-xs text-slate-500">{{ $ticket->created_at->diffForHumans() }}</p>
                    </td>
                    <td class="px-4 py-3">{{ $ticket->tenant?->name ?? 'Unknown' }}</td>
                    <td class="px-4 py-3 capitalize">{{ $ticket->priority }}</td>
                    <td class="px-4 py-3 capitalize">{{ str_replace('_', ' ', $ticket->status) }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('super-admin.support.show', $ticket) }}" class="text-sm font-semibold text-brand-600">Open</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">No tickets raised yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($tickets->hasPages())
    <div class="mt-4">{{ $tickets->links() }}</div>
@endif
@endsection
