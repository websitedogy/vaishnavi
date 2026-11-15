<!DOCTYPE html>
<html lang="te">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', $tenant->name)</title>
    @hasSection('meta_description')
        <meta name="description" content="@yield('meta_description')">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Telugu:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/tenant-site/site.css') }}">
    @stack('head')
</head>
<body class="ts-body">
@include('tenant-site.components.top-bar')
@include('tenant-site.components.logo-section')
@include('tenant-site.components.main-nav')

@if(isset($trendingTopics))
    @include('tenant-site.components.trending-bar')
@elseif(isset($breaking) && $breaking->isNotEmpty())
    <div class="ts-trending ts-trending--breaking">
        <div class="ts-container ts-trending__inner">
            <span class="ts-trending__badge ts-trending__badge--breaking">Breaking</span>
            <div class="ts-trending__track">
                @foreach($breaking as $item)
                    <a href="{{ $item->siteUrl($tenant) }}" class="ts-trending__item">{{ $item->displayTitle() }}</a>
                @endforeach
            </div>
        </div>
    </div>
@endif

<main class="ts-main @yield('main_class')">
    @yield('content')
</main>

@include('tenant-site.components.footer')

<script>
(function () {
    const nav = document.getElementById('ts-main-nav');
    const menuToggle = document.getElementById('ts-nav-toggle');

    menuToggle?.addEventListener('click', function () {
        const open = nav.classList.toggle('is-open');
        this.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    function closeAllDropdowns(except) {
        document.querySelectorAll('.ts-nav-item--parent.is-open').forEach(function (item) {
            if (except && item === except) return;
            item.classList.remove('is-open');
            const btn = item.querySelector('.ts-nav-expand');
            const panel = item.querySelector('.ts-nav-dropdown');
            if (btn) btn.setAttribute('aria-expanded', 'false');
            if (panel) panel.hidden = true;
        });
    }

    document.querySelectorAll('.ts-nav-expand').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const item = this.closest('.ts-nav-item--parent');
            const panel = item.querySelector('.ts-nav-dropdown');
            const willOpen = !item.classList.contains('is-open');
            closeAllDropdowns();
            if (willOpen) {
                item.classList.add('is-open');
                this.setAttribute('aria-expanded', 'true');
                panel.hidden = false;
            }
        });
    });

    document.addEventListener('click', function () {
        closeAllDropdowns();
    });
})();
</script>
@stack('scripts')
</body>
</html>
