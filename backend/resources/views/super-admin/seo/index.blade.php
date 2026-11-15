@extends('layouts.super-admin')

@section('title', 'SEO Global Settings')
@section('page-title', 'SEO Global Settings')
@section('page-subtitle', 'Sitemap, robots.txt, and meta defaults')

@section('content')
<div class="bg-white rounded-xl border border-slate-200 p-6 max-w-4xl">
    <form method="POST" action="{{ route('super-admin.seo.update') }}" class="space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Sitemap URL</label>
            <input type="url" name="sitemap_url" value="{{ $settings['sitemap_url'] }}" class="w-full rounded-lg border-slate-300 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Robots.txt</label>
            <textarea name="robots_txt" rows="8" class="w-full rounded-lg border-slate-300 text-sm font-mono">{{ $settings['robots_txt'] }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Default Meta Title</label>
            <input type="text" name="meta_title_default" value="{{ $settings['meta_title_default'] }}" class="w-full rounded-lg border-slate-300 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Default Meta Description</label>
            <textarea name="meta_description_default" rows="3" class="w-full rounded-lg border-slate-300 text-sm">{{ $settings['meta_description_default'] }}</textarea>
        </div>
        <button class="px-5 py-2.5 bg-brand-600 text-white rounded-lg text-sm font-semibold">Save SEO Settings</button>
    </form>
</div>
@endsection
