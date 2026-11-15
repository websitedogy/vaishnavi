@extends('layouts.tenant-site')

@section('title', $category->name.' — '.$tenant->name)

@section('content')
<div class="ts-container">
    <nav class="ts-breadcrumb">
        <a href="{{ route('tenant-site.home', $tenant->slug) }}">Home</a>
        @if($parentCategory)
            → <a href="{{ route('tenant-site.category', [$tenant->slug, $parentCategory->slug]) }}">{{ $parentCategory->name }}</a>
        @endif
        → <span>{{ $category->name }}</span>
    </nav>

    <h1 class="ts-section-title">{{ $category->name }}</h1>

    @include('tenant-site.components.subcategory-strip', [
        'tenant' => $tenant,
        'category' => $category,
        'subcategories' => $subcategories,
        'siblingSubcategories' => $siblingSubcategories ?? collect(),
        'parentCategory' => $parentCategory ?? null,
    ])

    @if($posts->isEmpty())
        <div class="ts-empty">
            @if($subcategories->isNotEmpty())
                <p>Select a subcategory above to read articles.</p>
            @else
                <p>No articles in this category yet.</p>
            @endif
        </div>
    @else
        <div class="ts-grid">
            @foreach($posts as $post)
                <a href="{{ $post->siteUrl($tenant) }}" class="ts-card">
                    @if($post->featured_image)
                        <img src="{{ \App\Models\MediaFile::normalizeStoredUrl($post->featured_image) }}" alt="">
                    @endif
                    <div class="ts-card__body">
                        @if($post->category)
                            <span class="ts-card__cat">{{ $post->category->name }}</span>
                        @endif
                        <h3 class="ts-card__title">{{ $post->displayTitle() }}</h3>
                        <p class="ts-card__meta">{{ $post->published_at?->format('M d, Y') }}</p>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="ts-pagination">{{ $posts->links('tenant-site.pagination') }}</div>
    @endif
</div>
@endsection
