@extends('layouts.tenant-site')

@section('title', 'Search'.($q ? ': '.$q : '').' — '.$tenant->name)

@section('content')
<div class="ts-container">
    <h1 class="ts-page-title">Search Results @if($q)<small>for “{{ $q }}”</small>@endif</h1>

    @if($posts->isEmpty())
        <div class="ts-empty">
            <p>No articles found.@if($q) Try different keywords.@endif</p>
        </div>
    @else
        <div class="ts-latest__grid">
            @foreach($posts as $news)
                @include('tenant-site.components.news-card', ['post' => $news, 'tenant' => $tenant, 'variant' => 'grid', 'showExcerpt' => true])
            @endforeach
        </div>
        <div class="ts-pagination">
            {{ $posts->appends(['q' => $q])->links('tenant-site.pagination') }}
        </div>
    @endif
</div>
@endsection
