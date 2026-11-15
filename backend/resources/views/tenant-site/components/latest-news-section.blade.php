<section class="ts-latest">
    <h2 class="ts-section-head">
        <span class="ts-section-head__bar"></span>
        Latest News
        <small>తాజా వార్తలు</small>
    </h2>

    @if($latestNews->isEmpty())
        <div class="ts-empty">
            <p>No published articles yet.</p>
            @auth('tenant_admin')
                <p>Publish posts from <a href="{{ route('tenant-admin.news.index') }}">tenant admin</a>.</p>
            @endauth
        </div>
    @else
        <div class="ts-latest__grid">
            @foreach($latestNews as $news)
                @include('tenant-site.components.news-card', ['post' => $news, 'tenant' => $tenant, 'variant' => 'grid', 'showExcerpt' => true])
            @endforeach
        </div>
    @endif
</section>
