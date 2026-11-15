@extends('layouts.tenant-admin')
@section('page-title','Theme & Website Customization')
@section('content')
<form method="POST" action="{{ route('tenant-admin.theme.update') }}" enctype="multipart/form-data" class="max-w-3xl">@csrf @method('PUT')
<div class="bg-white rounded-xl border p-6 space-y-4 mb-6">
<select name="theme_preset" class="w-full rounded-lg border-slate-300 text-sm"><option value="default">Default</option><option value="magazine">Magazine</option><option value="minimal">Minimal</option></select>
<label class="block text-sm">Logo <input type="file" name="logo" accept="image/*"></label>
<label class="block text-sm">Favicon <input type="file" name="favicon" accept="image/*"></label>
<input name="primary_color" type="color" value="{{ $settings['primary_color'] ?? '#2563eb' }}" class="h-10 w-20">
<input name="font_family" value="{{ $settings['font_family'] }}" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Font family">
<label class="flex gap-2 text-sm"><input type="checkbox" name="dark_mode" value="1" @checked($settings['dark_mode']??false)> Dark mode</label>
<select name="homepage_layout" class="w-full rounded-lg border-slate-300 text-sm"><option value="grid" @selected(($settings['homepage_layout']??'')==='grid')>Grid</option><option value="list" @selected(($settings['homepage_layout']??'')==='list')>List</option><option value="hero" @selected(($settings['homepage_layout']??'')==='hero')>Hero + list</option></select>
</div>
<div class="bg-white rounded-xl border p-6 space-y-4 mb-6">
<h3 class="font-semibold">Menu builder (JSON)</h3><textarea name="menu_json" rows="4" class="w-full rounded-lg border-slate-300 text-sm font-mono">{{ $settings['menu_json'] }}</textarea>
<h3 class="font-semibold">Widgets (JSON)</h3><textarea name="widgets_json" rows="4" class="w-full rounded-lg border-slate-300 text-sm font-mono">{{ $settings['widgets_json'] }}</textarea>
<h3 class="font-semibold">Header HTML</h3><textarea name="header_html" rows="3" class="w-full rounded-lg border-slate-300 text-sm">{{ $settings['header_html'] }}</textarea>
<h3 class="font-semibold">Footer editor</h3><textarea name="footer_html" rows="4" class="w-full rounded-lg border-slate-300 text-sm">{{ $settings['footer_html'] }}</textarea>
</div>
<button class="px-6 py-2.5 bg-brand-600 text-white font-semibold rounded-lg">Save theme</button>
</form>
@endsection
