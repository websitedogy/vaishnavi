@php
    $footerCategories = ($navCategories ?? collect())->take(10);
    $contactEmail = $tenant->email ?: 'contact@'.preg_replace('/[^a-z0-9.-]/', '', strtolower($tenant->slug)).'.com';
@endphp
<footer class="ts-site-footer">
    <div class="ts-site-footer__accent"></div>
    <div class="ts-container">
        <div class="ts-site-footer__main">
            <div class="ts-site-footer__col ts-site-footer__col--brand">
                <a href="{{ route('tenant-site.home', $tenant->slug) }}" class="ts-site-footer__logo">
                    @if($tenant->logo)
                        <img src="{{ \App\Models\MediaFile::normalizeStoredUrl($tenant->logo) }}" alt="{{ $tenant->name }}">
                    @endif
                    <span>{{ $tenant->name }}</span>
                </a>
                <p class="ts-site-footer__tagline">
                    విశ్వసనీయ తెలుగు వార్తల పోర్టల్ — బ్రేకింగ్ న్యూస్, రాజకీయాలు, సినిమా, బిజినెస్ &amp; మరిన్ని.
                </p>
                @include('tenant-site.components.social-share-links', ['variant' => 'footer'])
            </div>

            <div class="ts-site-footer__col">
                <h4 class="ts-site-footer__heading">Quick Links</h4>
                <ul class="ts-site-footer__list">
                    <li><a href="{{ route('tenant-site.home', $tenant->slug) }}">Home</a></li>
                    <li><a href="{{ route('tenant-site.home', $tenant->slug) }}#about">About Us</a></li>
                    <li><a href="{{ route('tenant-site.home', $tenant->slug) }}#contact">Contact Us</a></li>
                    <li><a href="{{ route('tenant-site.home', $tenant->slug) }}#privacy">Privacy Policy</a></li>
                    <li><a href="{{ route('tenant-site.home', $tenant->slug) }}#terms">Terms &amp; Conditions</a></li>
                    <li><a href="{{ route('tenant-site.search', $tenant->slug) }}">Search News</a></li>
                </ul>
            </div>

            <div class="ts-site-footer__col">
                <h4 class="ts-site-footer__heading">Categories</h4>
                <ul class="ts-site-footer__list">
                    @forelse($footerCategories as $cat)
                        <li>
                            <a href="{{ route('tenant-site.category', [$tenant->slug, $cat->slug]) }}">{{ $cat->name }}</a>
                        </li>
                    @empty
                        <li><a href="{{ route('tenant-site.home', $tenant->slug) }}">Latest News</a></li>
                    @endforelse
                </ul>
            </div>

            <div class="ts-site-footer__col" id="contact">
                <h4 class="ts-site-footer__heading">Contact Us</h4>
                <ul class="ts-site-footer__contact-list">
                    <li>
                        <span class="ts-site-footer__contact-label">Email</span>
                        <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
                    </li>
                    <li>
                        <span class="ts-site-footer__contact-label">Website</span>
                        <a href="{{ $tenant->publicSiteUrl() }}">{{ parse_url($tenant->publicSiteUrl(), PHP_URL_PATH) ?: '/' }}</a>
                    </li>
                </ul>
                <div class="ts-site-footer__newsletter" id="about">
                    <p class="ts-site-footer__newsletter-text">Daily news updates to your inbox</p>
                    <form class="ts-site-footer__newsletter-form" action="#" method="post" onsubmit="return false;">
                        <input type="email" placeholder="Your email address" aria-label="Email for newsletter">
                        <button type="submit">Subscribe</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="ts-site-footer__bottom">
            <p class="ts-site-footer__copy">
                &copy; {{ date('Y') }} <strong>{{ $tenant->name }}</strong>. All rights reserved.
            </p>
            <p class="ts-site-footer__powered">
                Powered by <a href="{{ route('home') }}" rel="noopener">Doggy SaaS News</a>
            </p>
        </div>
    </div>
</footer>
