@extends('layouts.tenant-site')

@section('title', $post->meta_title ?: $post->displayTitle().' — '.$tenant->name)
@section('meta_description', $post->meta_description ?: Str::limit(strip_tags($post->excerpt ?: $post->content), 160))

@section('content')
<div class="ts-container">
<div class="ts-article-layout">
<nav class="ts-breadcrumb">
    <a href="{{ route('tenant-site.home', $tenant->slug) }}">Home</a>
    @if($post->category)
        → <a href="{{ route('tenant-site.category', [$tenant->slug, $post->category->slug]) }}">{{ $post->category->name }}</a>
    @endif
    → <span>{{ $post->displayTitle() }}</span>
</nav>

<article class="ts-article">
    @if($post->category)<span class="ts-card__cat">{{ $post->category->name }}</span>@endif
    @if($post->is_breaking)<span class="ts-breaking__label" style="display:inline-block;margin-bottom:0.5rem">Breaking</span>@endif

    <h1>{{ $post->displayTitle() }}</h1>
    @if($post->subtitle)<p class="ts-article__subtitle">{{ $post->subtitle }}</p>@endif

    <p class="ts-article__meta">
        {{ $post->published_at?->format('F d, Y') }}
        @if($post->author) · {{ $post->author->name }} @endif
        · {{ number_format($post->views) }} views
    </p>

    @if($post->featured_image)
        <img class="ts-article__img" src="{{ \App\Models\MediaFile::normalizeStoredUrl($post->featured_image) }}" alt="">
    @endif

    <div class="ts-article__content">
        {!! $post->content !!}
    </div>

    @if($post->tags->isNotEmpty())
        <p class="ts-article__meta" style="margin-top:1.5rem;border:none">
            Tags:
            @foreach($post->tags as $tag)
                <span>{{ $tag->name }}</span>@if(!$loop->last), @endif
            @endforeach
        </p>
    @endif
</article>

@if($related->isNotEmpty())
    <h2 class="ts-section-title" style="margin-top:2rem">Related</h2>
    <div class="ts-grid">
        @foreach($related as $item)
            <a href="{{ $item->siteUrl($tenant) }}" class="ts-card">
                @if($item->featured_image)
                    <img src="{{ \App\Models\MediaFile::normalizeStoredUrl($item->featured_image) }}" alt="">
                @endif
                <div class="ts-card__body">
                    <h3 class="ts-card__title" style="font-size:0.95rem">{{ $item->displayTitle() }}</h3>
                </div>
            </a>
        @endforeach
    </div>
@endif
</div>{{-- .ts-article-layout --}}
</div>
@endsection
