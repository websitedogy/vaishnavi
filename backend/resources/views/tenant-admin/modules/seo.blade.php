@extends('layouts.tenant-admin')
@section('page-title','SEO Features')
@section('content')
<form method="POST" action="{{ route('tenant-admin.seo.update') }}" class="max-w-3xl">@csrf @method('PUT')
<div class="bg-white rounded-xl border p-6 space-y-4 mb-6"><h2 class="font-semibold mb-3">Meta & Open Graph</h2>
<input name="meta_title" value="{{ $settings['meta_title'] }}" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Default meta title">
<textarea name="meta_description" rows="2" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Meta description">{{ $settings['meta_description'] }}</textarea>
<input name="og_image" value="{{ $settings['og_image'] }}" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Open Graph image URL">
<input name="twitter_handle" value="{{ $settings['twitter_handle'] }}" class="w-full rounded-lg border-slate-300 text-sm" placeholder="@yournews">
<input name="canonical_base" value="{{ $settings['canonical_base'] }}" class="w-full rounded-lg border-slate-300 text-sm" placeholder="https://yoursite.com">
</div>
<div class="bg-white rounded-xl border p-6 space-y-4 mb-6"><h2 class="font-semibold mb-3">Sitemap, robots & schema</h2>
<label class="flex gap-2 text-sm"><input type="checkbox" name="sitemap_enabled" value="1" @checked($settings['sitemap_enabled']??true)> XML sitemap enabled</label>
<a href="{{ route('tenant-admin.sitemap') }}" target="_blank" class="text-sm text-brand-600">View sitemap.xml →</a>
<textarea name="robots_txt" rows="4" class="w-full rounded-lg border-slate-300 text-sm font-mono">{{ $settings['robots_txt'] }}</textarea>
<input name="schema_type" value="{{ $settings['schema_type'] }}" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Schema.org type">
</div>
<div class="bg-white rounded-xl border p-6 space-y-4 mb-6"><h2 class="font-semibold mb-3">Google Analytics & Search Console</h2>
<input name="google_analytics_id" value="{{ $settings['google_analytics_id'] }}" class="w-full rounded-lg border-slate-300 text-sm" placeholder="G-XXXXXXXX">
<input name="search_console_verification" value="{{ $settings['search_console_verification'] }}" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Verification meta content">
</div>
<button class="px-6 py-2.5 bg-brand-600 text-white font-semibold rounded-lg">Save SEO settings</button>
</form>
@endsection
