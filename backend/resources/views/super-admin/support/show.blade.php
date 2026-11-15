@extends('layouts.super-admin')

@section('title', 'Support Ticket')
@section('page-title', 'Support Ticket #'.$ticket->id)
@section('page-subtitle', $ticket->subject)

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-sm text-slate-500 mb-2">Original message</p>
            <p class="text-slate-800">{{ $ticket->message }}</p>
            <p class="text-xs text-slate-500 mt-3">{{ $ticket->tenant?->name }} · {{ $ticket->user?->name ?? 'Tenant User' }}</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h2 class="font-semibold text-slate-900 mb-4">Chat / Reply System</h2>
            <div class="space-y-3 max-h-80 overflow-y-auto mb-4">
                @forelse ($ticket->replies as $reply)
                    <div class="p-3 rounded-lg {{ $reply->super_admin_id ? 'bg-brand-50 border border-brand-100' : 'bg-slate-50 border border-slate-100' }}">
                        <p class="text-sm text-slate-800">{{ $reply->message }}</p>
                        <p class="text-xs text-slate-500 mt-2">
                            {{ $reply->superAdmin?->name ?? $reply->user?->name ?? 'System' }}
                            · {{ $reply->created_at->diffForHumans() }}
                            @if ($reply->is_internal) · Internal @endif
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No replies yet.</p>
                @endforelse
            </div>

            <form method="POST" action="{{ route('super-admin.support.reply', $ticket) }}" class="space-y-3">
                @csrf
                <textarea name="message" rows="4" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Type your reply..." required></textarea>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_internal" value="1" class="rounded text-brand-600">
                    Internal note (not visible to tenant)
                </label>
                <button class="px-5 py-2.5 bg-brand-600 text-white rounded-lg text-sm font-semibold">Send Reply</button>
            </form>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h2 class="font-semibold text-slate-900 mb-3">Ticket Management</h2>
            <form method="POST" action="{{ route('super-admin.support.update', $ticket) }}" class="space-y-3">
                @csrf
                @method('PUT')
                <select name="status" class="w-full rounded-lg border-slate-300 text-sm">
                    @foreach (['open', 'in_progress', 'resolved', 'closed'] as $status)
                        <option value="{{ $status }}" @selected($ticket->status === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
                <button class="w-full py-2.5 bg-slate-900 text-white rounded-lg text-sm font-semibold">Update Status</button>
            </form>
        </div>
    </div>
</div>
@endsection
