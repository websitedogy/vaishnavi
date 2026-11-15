<aside class="ts-widget ts-widget--ad">
    @if(!empty($sidebarAd))
        @if($sidebarAd->link_url)
            <a href="{{ $sidebarAd->link_url }}" target="_blank" rel="noopener sponsored" class="ts-ad-block">
        @else
            <div class="ts-ad-block">
        @endif
            @if($sidebarAd->image_url)
                <img src="{{ \App\Models\MediaFile::normalizeStoredUrl($sidebarAd->image_url) }}" alt="{{ $sidebarAd->name }}">
            @elseif($sidebarAd->content)
                {!! $sidebarAd->content !!}
            @else
                <div class="ts-ad-placeholder">
                    <span>Advertisement</span>
                    <small>{{ $sidebarAd->name }}</small>
                </div>
            @endif
        @if($sidebarAd->link_url)
            </a>
        @else
            </div>
        @endif
    @else
        <div class="ts-ad-placeholder">
            <span>Advertisement</span>
            <small>300 × 250</small>
        </div>
    @endif
</aside>
