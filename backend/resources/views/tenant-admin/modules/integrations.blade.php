@extends('layouts.tenant-admin')
@section('page-title','API & Integrations')
@section('content')
<form method="POST" action="{{ route('tenant-admin.integrations.update') }}" class="max-w-2xl mb-8">@csrf @method('PUT')
<div class="bg-white rounded-xl border p-6 space-y-3">
<input name="rss_import_url" value="{{ $settings['rss_import_url'] }}" class="w-full rounded-lg border-slate-300 text-sm" placeholder="RSS import URL">
<label class="flex gap-2 text-sm"><input type="checkbox" name="rss_export_enabled" value="1" @checked($settings['rss_export_enabled']??true)> RSS export</label>
<input name="youtube_api_key" value="{{ $settings['youtube_api_key'] }}" class="w-full rounded-lg border-slate-300 text-sm" placeholder="YouTube API key">
<textarea name="google_services_json" rows="3" class="w-full rounded-lg border-slate-300 text-sm font-mono" placeholder="Google services JSON">{{ $settings['google_services_json'] }}</textarea>
<button class="px-6 py-2 bg-brand-600 text-white rounded-lg text-sm">Save integrations</button>
</div>
</form>
<h2 class="font-semibold mb-3">REST API keys</h2>
<form method="POST" action="{{ route('tenant-admin.integrations.api-keys.store') }}" class="flex gap-2 mb-4">@csrf<input name="name" required placeholder="Key name" class="rounded-lg border-slate-300 text-sm flex-1"><button class="px-4 py-2 bg-brand-600 text-white text-sm rounded-lg">Generate key</button></form>
<ul class="space-y-2">@foreach($apiKeys as $k)<li class="bg-white border rounded-lg p-3 flex justify-between text-sm"><span>{{ $k->name }} <code class="text-xs">{{ $k->key_prefix }}…</code></span>
<form method="POST" action="{{ route('tenant-admin.integrations.api-keys.destroy',$k) }}">@csrf @method('DELETE')<button class="text-rose-600">Revoke</button></form></li>@endforeach</ul>
<p class="text-xs text-slate-500 mt-4">Webhooks: configure per key in API settings. REST base: <code>/api/v1/tenant</code> (wire when deploying).</p>
@endsection
