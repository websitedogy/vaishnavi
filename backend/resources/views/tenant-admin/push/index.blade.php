@extends('layouts.tenant-admin')
@section('page-title','Push Notifications')
@section('content')
<div class="grid lg:grid-cols-2 gap-6">
<div><h2 class="font-semibold mb-3">Send / schedule</h2>
<form method="POST" action="{{ route('tenant-admin.push.store') }}" class="bg-white rounded-xl border p-5 space-y-3">@csrf
<input name="title" required class="w-full rounded-lg border-slate-300 text-sm" placeholder="Title">
<textarea name="body" required rows="3" class="w-full rounded-lg border-slate-300 text-sm"></textarea>
<input name="topic" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Topic (e.g. breaking)">
<select name="channel" class="w-full rounded-lg border-slate-300 text-sm"><option value="browser">Browser</option><option value="mobile">Mobile push</option></select>
<input type="datetime-local" name="scheduled_at" class="w-full rounded-lg border-slate-300 text-sm">
<button class="w-full py-2 bg-brand-600 text-white rounded-lg text-sm">Save notification</button></form>
<ul class="mt-4 space-y-2">@foreach($notifications as $n)<li class="text-sm bg-white border rounded-lg p-3">{{ $n->title }} — <span class="text-slate-500">{{ $n->status }}</span></li>@endforeach</ul>
</div>
<div><p class="text-sm mb-3">Subscribers: <strong>{{ $subscriberCount }}</strong></p>
<form method="POST" action="{{ route('tenant-admin.push.config') }}">@csrf @method('PUT')
<div class="bg-white rounded-xl border p-5 space-y-3">
<label class="flex gap-2 text-sm"><input type="checkbox" name="browser_enabled" value="1" @checked($config['browser_enabled']??true)> Browser notifications</label>
<label class="flex gap-2 text-sm"><input type="checkbox" name="mobile_enabled" value="1" @checked($config['mobile_enabled']??false)> Mobile push</label>
<input name="firebase_key" value="{{ $config['firebase_key'] ?? '' }}" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Firebase server key">
<input name="default_topic" value="{{ $config['default_topic'] ?? 'general' }}" class="w-full rounded-lg border-slate-300 text-sm">
<button class="w-full py-2 border rounded-lg text-sm">Save config</button></div></form></div></div>
@endsection
