@if(isset($trendingTopics) && $trendingTopics->isNotEmpty())
<div class="ts-trending">
    <div class="ts-container ts-trending__inner">
        <span class="ts-trending__badge">Trending ⚡</span>
        <div class="ts-trending__track">
            @foreach($trendingTopics as $topic)
                <a href="{{ $topic->siteUrl($tenant) }}" class="ts-trending__item">{{ $topic->displayTitle() }}</a>
            @endforeach
        </div>
    </div>
</div>
@endif
