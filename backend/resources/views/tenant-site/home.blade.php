@extends('layouts.tenant-site')

@section('title', $tenant->name.' — Telugu News Portal')
@section('main_class', 'ts-main--home')

@section('content')
<div class="ts-container">
    <div class="ts-home-layout">
        <div class="ts-home-main">
            @include('tenant-site.components.featured-section', ['featured' => $featured, 'tenant' => $tenant])
            @include('tenant-site.components.latest-news-section', ['latestNews' => $latestNews, 'tenant' => $tenant])
        </div>

        <aside class="ts-home-sidebar">
            @include('tenant-site.components.sidebar.top-stories', ['topStories' => $topStories, 'tenant' => $tenant])
            @include('tenant-site.components.sidebar.popular', ['popularNews' => $popularNews, 'tenant' => $tenant])
            @include('tenant-site.components.sidebar.ad', ['sidebarAd' => $sidebarAd ?? null])
        </aside>
    </div>
</div>
@endsection
