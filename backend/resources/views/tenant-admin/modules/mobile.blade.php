@extends('layouts.tenant-admin')
@section('page-title','Mobile App Control')
@section('content')
<form method="POST" action="{{ route('tenant-admin.mobile.update') }}" class="max-w-2xl">@csrf @method('PUT')
<div class="bg-white rounded-xl border p-6 space-y-4">
<textarea name="homepage_sections" rows="5" class="w-full rounded-lg border-slate-300 text-sm font-mono" placeholder='[{"title":"Top Stories","type":"featured"}]'>{{ $settings['homepage_sections'] }}</textarea>
<input name="splash_image" value="{{ $settings['splash_image'] }}" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Splash screen image URL">
<input name="splash_duration" type="number" value="{{ $settings['splash_duration'] ?? 3 }}" class="w-full rounded-lg border-slate-300 text-sm">
<label class="flex gap-2 text-sm"><input type="checkbox" name="app_ads_enabled" value="1" @checked($settings['app_ads_enabled']??true)> App ads management</label>
<label class="flex gap-2 text-sm"><input type="checkbox" name="sync_theme" value="1" @checked($settings['sync_theme']??true)> Sync theme with website</label>
<p class="text-xs text-slate-500">Push notifications: <a href="{{ route('tenant-admin.push.index') }}" class="text-brand-600">Push settings →</a></p>
<button class="px-6 py-2.5 bg-brand-600 text-white font-semibold rounded-lg">Save mobile settings</button>
</div>
</form>
@endsection
