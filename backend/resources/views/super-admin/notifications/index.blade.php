@extends('layouts.super-admin')

@section('title', 'Notification Management')
@section('page-title', 'Notification Management')
@section('page-subtitle', 'Global announcements and email alerts')

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
    <div class="xl:col-span-1 bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-900 mb-3">Send Global Notification</h2>
        <form method="POST" action="{{ route('super-admin.notifications.store') }}" class="space-y-3">
            @csrf
            <select name="type" class="w-full rounded-lg border-slate-300 text-sm">
                <option value="announcement">Announcement</option>
                <option value="expiry_alert">Expiry Alert</option>
                <option value="maintenance_alert">Maintenance Alert</option>
            </select>
            <input type="text" name="title" placeholder="Title" required class="w-full rounded-lg border-slate-300 text-sm">
            <textarea name="message" rows="4" placeholder="Message to all tenants..." required class="w-full rounded-lg border-slate-300 text-sm"></textarea>
            <div class="space-y-1">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="channels[]" value="in_app" checked class="rounded text-brand-600"> In-app</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="channels[]" value="email" checked class="rounded text-brand-600"> Email Alerts</label>
            </div>
            <button class="w-full py-2.5 rounded-lg bg-brand-600 text-white text-sm font-semibold">Send Notification</button>
        </form>
    </div>

    <div class="xl:col-span-2 bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-900 mb-4">Notification History</h2>
        <div class="space-y-3 max-h-[420px] overflow-y-auto">
            @forelse ($notifications as $notification)
                <div class="p-3 rounded-lg bg-slate-50 border border-slate-100">
                    <div class="flex items-center justify-between">
                        <p class="font-medium text-slate-800">{{ $notification->title }}</p>
                        <span class="text-xs px-2 py-1 rounded bg-slate-200 text-slate-700 uppercase">{{ str_replace('_', ' ', $notification->type) }}</span>
                    </div>
                    <p class="text-sm text-slate-600 mt-1">{{ $notification->message }}</p>
                    <p class="text-xs text-slate-500 mt-2">
                        Channels: {{ implode(', ', $notification->channels ?? ['in_app']) }}
                        · {{ $notification->sent_at?->diffForHumans() ?? 'Pending' }}
                    </p>
                </div>
            @empty
                <p class="text-sm text-slate-500">No notifications sent yet.</p>
            @endforelse
        </div>
    </div>
</div>

@if ($notifications->hasPages())
    <div>{{ $notifications->links() }}</div>
@endif
@endsection
