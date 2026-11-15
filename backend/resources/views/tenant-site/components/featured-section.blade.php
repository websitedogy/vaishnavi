@if($featured->isNotEmpty())
<section class="ts-featured">
    @php $lead = $featured->first(); @endphp
    <a href="{{ $lead->siteUrl($tenant) }}" class="ts-featured__lead">
        <div class="ts-featured__lead-img">
            @if($lead->featured_image)
                <img src="{{ \App\Models\MediaFile::normalizeStoredUrl($lead->featured_image) }}" alt="">
            @else
                <div class="ts-news-card__placeholder"></div>
            @endif
        </div>
        <div class="ts-featured__lead-body">
            @if($lead->category)
                <span class="ts-news-card__cat">{{ $lead->category->name }}</span>
            @endif
            <h2 class="ts-featured__title">{{ $lead->displayTitle() }}</h2>
            @if($lead->subtitle)
                <p class="ts-featured__subtitle">{{ $lead->subtitle }}</p>
            @endif
            <p class="ts-featured__meta">
                <time datetime="{{ $lead->published_at?->toIso8601String() }}">{{ $lead->published_at?->format('F d, Y') }}</time>
            </p>
        </div>
    </a>

    @if($featured->count() > 1)
    <div class="ts-featured__grid">
        @foreach($featured->skip(1)->take(4) as $news)
            @include('tenant-site.components.news-card', ['post' => $news, 'tenant' => $tenant, 'variant' => 'compact'])
        @endforeach
    </div>
    @endif
</section>
@endif
