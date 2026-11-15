@extends('layouts.super-admin')
@section('title', 'Edit Theme')
@section('page-title', 'Theme: '.$tenant->name)
@section('page-subtitle', 'Typography, colors, layout & feature toggles')

@section('content')
<form method="POST" action="{{ route('super-admin.themes.update', $tenant) }}" class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    @csrf @method('PUT')

    <div class="xl:col-span-2 space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="font-semibold text-slate-900 mb-4">Theme & Layout</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Theme Preset</label>
                    <select name="theme_preset" class="w-full rounded-lg border-slate-300 text-sm">
                        @foreach($presets as $key => $preset)
                        <option value="{{ $key }}" @selected(old('theme_preset', $theme->theme_preset) === $key)>{{ $preset['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Layout</label>
                    <select name="layout" class="w-full rounded-lg border-slate-300 text-sm">
                        @foreach($layouts as $layout)
                        <option value="{{ $layout }}" @selected(old('layout', $theme->layout) === $layout)>{{ ucfirst($layout) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Typography</label>
                    <select name="font_family" class="w-full rounded-lg border-slate-300 text-sm">
                        @foreach($fonts as $name => $stack)
                        <option value="{{ $name }}" @selected(old('font_family', $theme->font_family) === $name)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="font-semibold text-slate-900 mb-4">Colors</h2>
            <div class="grid grid-cols-3 gap-4">
                @foreach(['primary_color' => 'Primary', 'secondary_color' => 'Secondary', 'accent_color' => 'Accent'] as $field => $label)
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ $label }}</label>
                    <input type="color" name="{{ $field }}" value="{{ old($field, $theme->$field) }}" class="w-full h-10 rounded cursor-pointer">
                </div>
                @endforeach
            </div>
            <div id="theme-preview" class="mt-4 p-4 rounded-lg border" style="font-family: {{ $fonts[$theme->font_family] ?? 'sans-serif' }}; background: {{ $theme->secondary_color }}; color: white;">
                <p class="text-lg font-bold" style="color: {{ $theme->primary_color }}">Headline Preview</p>
                <p class="text-sm mt-1" style="color: {{ $theme->accent_color }}">Accent link sample</p>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="font-semibold text-slate-900 mb-1">Enable / Disable Features</h2>
            <p class="text-xs text-slate-500 mb-4">Toggle modules for this tenant site</p>
            @php $f = $theme->features ?? \App\Models\TenantTheme::defaultFeatures(); @endphp
            @foreach($features as $key => $label)
            <label class="flex items-center justify-between py-3 border-b border-slate-100 last:border-0">
                <span class="text-sm font-medium text-slate-700">{{ $label }}</span>
                <input type="checkbox" name="feature_{{ $key }}" value="1" class="rounded text-brand-600 w-5 h-5" @checked(old('feature_'.$key, $f[$key] ?? false))>
            </label>
            @endforeach
        </div>
        <button type="submit" class="w-full py-2.5 bg-brand-600 text-white font-semibold rounded-lg hover:bg-brand-700">Save Theme</button>
        <a href="{{ route('super-admin.themes.index') }}" class="block w-full py-2.5 text-center border rounded-lg text-sm hover:bg-slate-50">Back</a>
    </div>
</form>
@endsection
