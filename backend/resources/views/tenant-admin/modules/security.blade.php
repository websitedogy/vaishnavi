@extends('layouts.tenant-admin')
@section('page-title','Security')
@section('content')
<form method="POST" action="{{ route('tenant-admin.security.update') }}" class="max-w-2xl mb-8">@csrf @method('PUT')
<div class="bg-white rounded-xl border p-6 space-y-3">
<label class="flex gap-2 text-sm"><input type="checkbox" name="two_factor_required" value="1" @checked($settings['two_factor_required']??false)> Require 2FA for staff</label>
<textarea name="ip_whitelist" rows="3" class="w-full rounded-lg border-slate-300 text-sm font-mono" placeholder="IP restrictions (one per line)">{{ $settings['ip_whitelist'] }}</textarea>
<input name="session_timeout_minutes" type="number" value="{{ $settings['session_timeout_minutes'] ?? 120 }}" class="w-full rounded-lg border-slate-300 text-sm">
<label class="flex gap-2 text-sm"><input type="checkbox" name="backup_download_enabled" value="1" @checked($settings['backup_download_enabled']??true)> Allow backup downloads</label>
<button class="px-6 py-2 bg-brand-600 text-white rounded-lg text-sm">Save security</button>
</div>
</form>
<h2 class="font-semibold mb-3">Login history</h2>
<div class="bg-white rounded-xl border overflow-hidden text-sm">
<table class="w-full"><thead class="bg-slate-50"><tr><th class="p-3 text-left">Time</th><th class="p-3">Email</th><th class="p-3">IP</th><th class="p-3">OK</th></tr></thead>
<tbody>@foreach($logins as $l)<tr class="border-t"><td class="p-3">{{ $l->attempted_at?->format('M d H:i') }}</td><td class="p-3">{{ $l->email }}</td><td class="p-3">{{ $l->ip_address }}</td><td class="p-3">{{ $l->is_success ? 'Yes' : 'No' }}</td></tr>@endforeach</tbody></table>
</div>
<p class="text-sm text-slate-500 mt-2">Activity logs: <a href="{{ route('tenant-admin.staff.logs') }}" class="text-brand-600">Staff logs →</a></p>
@endsection
