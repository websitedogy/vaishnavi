<aside class="ts-widget">
    <h3 class="ts-widget__title">Top Stories</h3>
    <ul class="ts-story-list">
        @forelse($topStories as $story)
            <li class="ts-story-list__item">
                <time datetime="{{ $story->published_at?->toIso8601String() }}">{{ $story->published_at?->format('M d') }}</time>
                <a href="{{ $story->siteUrl($tenant) }}">{{ $story->displayTitle() }}</a>
            </li>
        @empty
            <li class="ts-story-list__empty">No stories yet.</li>
        @endforelse
    </ul>
</aside>
