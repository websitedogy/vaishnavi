@php
    $variant = $variant ?? 'default';
    $showExcerpt = $showExcerpt ?? false;
    $imageUrl = $post->featured_image
        ? \App\Models\MediaFile::normalizeStoredUrl($post->featured_image)
        : null;
@endphp
<a href="{{ $post->siteUrl($tenant) }}" class="ts-news-card ts-news-card--{{ $variant }}">
    <div class="ts-news-card__media">
        @if($imageUrl)
            <img src="{{ $imageUrl }}" alt="" loading="lazy">
        @else
            <div class="ts-news-card__placeholder" aria-hidden="true"></div>
        @endif
        @if($post->is_trending)
            <span class="ts-news-card__badge">Trending</span>
        @endif
    </div>
    <div class="ts-news-card__body">
        @if($post->category)
            <span class="ts-news-card__cat">{{ $post->category->name }}</span>
        @endif
        <h3 class="ts-news-card__title">{{ $post->displayTitle() }}</h3>
        @if($showExcerpt && $post->excerpt)
            <p class="ts-news-card__excerpt">{{ Str::limit(strip_tags($post->excerpt), 90) }}</p>
        @endif
        <time class="ts-news-card__date" datetime="{{ $post->published_at?->toIso8601String() }}">
            {{ $post->published_at?->format('M d, Y') ?? 'Recently' }}
        </time>
    </div>
</a>
