<aside class="ts-widget">
    <h3 class="ts-widget__title">Popular News</h3>
    <ul class="ts-popular-list">
        @forelse($popularNews as $news)
            <li class="ts-popular-list__item">
                <a href="{{ $news->siteUrl($tenant) }}" class="ts-popular-list__link">
                    <span class="ts-popular-list__num">{{ $loop->iteration }}</span>
                    <span class="ts-popular-list__text">{{ $news->displayTitle() }}</span>
                </a>
                <span class="ts-popular-list__views">{{ number_format($news->views) }} views</span>
            </li>
        @empty
            <li class="ts-story-list__empty">No popular posts yet.</li>
        @endforelse
    </ul>
</aside>
