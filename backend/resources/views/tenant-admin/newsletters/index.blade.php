@extends('layouts.tenant-admin')
@section('page-title','Newsletter System')
@section('content')
<div class="grid sm:grid-cols-2 gap-4 mb-6">
<div class="bg-white rounded-xl border p-4"><p class="text-sm text-slate-500">Subscribers</p><p class="text-2xl font-bold">{{ $stats['total_subscribers'] }}</p></div>
<div class="bg-white rounded-xl border p-4"><p class="text-sm text-slate-500">Campaigns sent</p><p class="text-2xl font-bold">{{ $stats['campaigns_sent'] }}</p></div>
</div>
<div class="grid lg:grid-cols-2 gap-6">
<div><h3 class="font-semibold mb-2">New campaign</h3>
<form method="POST" action="{{ route('tenant-admin.newsletters.campaigns.store') }}" class="bg-white border rounded-xl p-5 space-y-3">@csrf
<input name="subject" required class="w-full rounded-lg border-slate-300 text-sm">
<textarea name="body" required rows="5" class="w-full rounded-lg border-slate-300 text-sm" placeholder="HTML mail template body"></textarea>
<input type="datetime-local" name="scheduled_at" class="w-full rounded-lg border-slate-300 text-sm">
<button class="w-full py-2 bg-brand-600 text-white text-sm rounded-lg">Create campaign</button></form>
@foreach($campaigns as $c)<div class="mt-3 bg-white border rounded-lg p-3 text-sm flex justify-between"><span>{{ $c->subject }} ({{ $c->status }})</span>
@if($c->status!=='sent')<form method="POST" action="{{ route('tenant-admin.newsletters.campaigns.send',$c) }}">@csrf<button class="text-brand-600">Send now</button></form>@endif</div>@endforeach
</div>
<div><h3 class="font-semibold mb-2">Add subscriber</h3>
<form method="POST" action="{{ route('tenant-admin.newsletters.subscribers.store') }}" class="bg-white border rounded-xl p-5 space-y-2 mb-4">@csrf
<input name="email" type="email" required class="w-full rounded-lg border-slate-300 text-sm">
<input name="name" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Name">
<button class="w-full py-2 border rounded-lg text-sm">Add</button></form>
<ul class="text-sm space-y-1">@foreach($subscribers as $s)<li class="bg-white border rounded px-3 py-2">{{ $s->email }}</li>@endforeach</ul>
{{ $subscribers->links() }}</div></div>
@endsection
