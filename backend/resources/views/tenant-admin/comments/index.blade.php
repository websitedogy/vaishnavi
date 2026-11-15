@extends('layouts.tenant-admin')
@section('page-title','Comment Management')
@section('content')
<div class="grid lg:grid-cols-3 gap-6">
<div class="lg:col-span-2 space-y-4">
<div class="flex gap-2"><a href="{{ route('tenant-admin.comments.index') }}" class="px-3 py-1.5 text-sm rounded-lg border {{ !request('queue')?'bg-brand-600 text-white':'' }}">All</a>
<a href="{{ route('tenant-admin.comments.index',['queue'=>'pending']) }}" class="px-3 py-1.5 text-sm rounded-lg border {{ request('queue')==='pending'?'bg-brand-600 text-white':'' }}">Moderation queue</a>
<a href="{{ route('tenant-admin.comments.index',['queue'=>'spam']) }}" class="px-3 py-1.5 text-sm rounded-lg border {{ request('queue')==='spam'?'bg-brand-600 text-white':'' }}">Spam</a></div>
@foreach($comments as $c)<div class="bg-white rounded-xl border p-4"><p class="font-medium">{{ $c->author_name }} <span class="text-slate-400 text-xs">{{ $c->post?->title }}</span></p><p class="text-sm mt-1">{{ $c->body }}</p>
<div class="mt-2 flex gap-2">@unless($c->is_approved)<form method="POST" action="{{ route('tenant-admin.comments.approve',$c) }}">@csrf<button class="text-xs text-emerald-600 font-medium">Approve</button></form>@endunless
<form method="POST" action="{{ route('tenant-admin.comments.destroy',$c) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-xs text-rose-600">Delete</button></form></div></div>@endforeach
{{ $comments->links() }}
</div>
<div class="space-y-4">
<form method="POST" action="{{ route('tenant-admin.comments.settings') }}" class="bg-white rounded-xl border p-5 space-y-3">@csrf
<h3 class="font-semibold">Settings</h3>
<label class="flex gap-2 text-sm"><input type="checkbox" name="comments_enabled" value="1" @checked($config['comments_enabled']??true)> Enable comments</label>
<label class="flex gap-2 text-sm"><input type="checkbox" name="moderation_queue" value="1" @checked($config['moderation_queue']??true)> Moderation queue</label>
<label class="flex gap-2 text-sm"><input type="checkbox" name="auto_flag_spam" value="1" @checked($config['auto_flag_spam']??true)> Auto spam filter</label>
<textarea name="spam_keywords" rows="3" class="w-full rounded-lg border-slate-300 text-sm">{{ $config['spam_keywords']??'' }}</textarea>
<button class="w-full py-2 bg-brand-600 text-white text-sm rounded-lg">Save</button></form>
<form method="POST" action="{{ route('tenant-admin.comments.block') }}" class="bg-white rounded-xl border p-5 space-y-2">@csrf<h3 class="font-semibold">Block user</h3>
<input name="email" placeholder="Email" class="w-full rounded-lg border-slate-300 text-sm"><input name="ip_address" placeholder="IP" class="w-full rounded-lg border-slate-300 text-sm">
<button class="w-full py-2 border text-sm rounded-lg">Block</button></form>
</div></div>
@endsection
