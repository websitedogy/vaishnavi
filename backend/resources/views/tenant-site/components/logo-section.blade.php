<div class="ts-logo-section">
    <div class="ts-container ts-logo-section__inner">
        <a href="{{ route('tenant-site.home', $tenant->slug) }}" class="ts-brand">
            @if($tenant->logo)
                <img src="{{ \App\Models\MediaFile::normalizeStoredUrl($tenant->logo) }}" alt="{{ $tenant->name }}" class="ts-brand__img">
            @else
                <span class="ts-brand__icon" aria-hidden="true">📰</span>
            @endif
            <span class="ts-brand__title">{{ $tenant->name }}</span>
        </a>

        <a href="#" class="ts-epaper" title="E-Paper">
            <span class="ts-epaper__label">E-Paper</span>
            <span class="ts-epaper__sub">డిజిటల్ ఎడిషన్</span>
        </a>

        <div class="ts-logo-section__right">
            @include('tenant-site.components.social-share-links', ['variant' => 'header'])
            <form class="ts-search" action="{{ route('tenant-site.search', $tenant->slug) }}" method="get" role="search">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="వార్తలు వెతకండి…" aria-label="Search news">
                <button type="submit" aria-label="Search">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3-3"/></svg>
                </button>
            </form>
        </div>
    </div>
</div>
