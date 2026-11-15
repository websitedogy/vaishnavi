<header class="sa-navbar">
    <div class="sa-navbar__inner">
        <div class="sa-navbar__left">
            <button type="button" class="sa-navbar__menu-btn" onclick="toggleSidebar()" aria-label="Open menu">
                <svg class="sa-navbar__menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="sa-navbar__titles">
                <h1 class="sa-navbar__page-title">@yield('page-title', 'Dashboard')</h1>
                @hasSection('page-subtitle')
                    <p class="sa-navbar__page-subtitle">@yield('page-subtitle')</p>
                @endif
            </div>
        </div>
        <div class="sa-navbar__actions">
            @yield('header-actions')
        </div>
    </div>
</header>
