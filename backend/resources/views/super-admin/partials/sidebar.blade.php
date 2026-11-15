<div class="sa-sidebar-overlay" onclick="toggleSidebar()" aria-hidden="true"></div>

<aside class="sa-sidebar" id="sa-sidebar">
    <div class="sa-sidebar__brand">
        <div class="sa-sidebar__logo">NC</div>
        <div>
            <p class="sa-sidebar__brand-title">News CMS</p>
            <p class="sa-sidebar__brand-subtitle">Super Admin</p>
        </div>
    </div>

    <nav class="sa-sidebar__nav" aria-label="Main navigation">
        @php
            $navClass = function ($active) {
                return 'sa-sidebar__link' . ($active ? ' sa-sidebar__link--active' : '');
            };
        @endphp

        <a href="{{ route('super-admin.dashboard') }}" class="{{ $navClass(request()->routeIs('super-admin.dashboard')) }}">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            Dashboard
        </a>

        <p class="sa-sidebar__section-label">Platform</p>
        <a href="{{ route('super-admin.tenants.index') }}" class="{{ $navClass(request()->routeIs('super-admin.tenants.*')) }}">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
            Tenants
        </a>
        <a href="{{ route('super-admin.domains.index') }}" class="{{ $navClass(request()->routeIs('super-admin.domains.*')) }}">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
            Domains & SSL
        </a>
        <a href="{{ route('super-admin.users.index') }}" class="{{ $navClass(request()->routeIs('super-admin.users.*')) }}">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            User Management
        </a>

        <p class="sa-sidebar__section-label">Content & Ads</p>
        <a href="{{ route('super-admin.themes.index') }}" class="{{ $navClass(request()->routeIs('super-admin.themes.*')) }}">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
            Theme Management
        </a>
        <a href="{{ route('super-admin.advertisements.index') }}" class="{{ $navClass(request()->routeIs('super-admin.advertisements.*')) }}">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
            Advertisements
        </a>

        <p class="sa-sidebar__section-label">Ops & Reports</p>
        <a href="{{ route('super-admin.analytics.index') }}" class="{{ $navClass(request()->routeIs('super-admin.analytics.*')) }}">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3v18m4-14v14m4-10v10M7 7v14"/></svg>
            Analytics & Reports
        </a>
        <a href="{{ route('super-admin.security.index') }}" class="{{ $navClass(request()->routeIs('super-admin.security.*')) }}">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l8 4v6c0 5-3.5 8.5-8 9-4.5-.5-8-4-8-9V7l8-4z"/></svg>
            Security
        </a>
        <a href="{{ route('super-admin.backups.index') }}" class="{{ $navClass(request()->routeIs('super-admin.backups.*')) }}">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16"/></svg>
            Backup & Restore
        </a>
        <a href="{{ route('super-admin.notifications.index') }}" class="{{ $navClass(request()->routeIs('super-admin.notifications.*')) }}">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4a2 2 0 01-.6-1.4V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0h6z"/></svg>
            Notifications
        </a>

        <p class="sa-sidebar__section-label">Platform Controls</p>
        <a href="{{ route('super-admin.storage.index') }}" class="{{ $navClass(request()->routeIs('super-admin.storage.*')) }}">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16v10H4zM8 11h8"/></svg>
            Storage Management
        </a>
        <a href="{{ route('super-admin.api.index') }}" class="{{ $navClass(request()->routeIs('super-admin.api.*')) }}">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
            API Management
        </a>
        <a href="{{ route('super-admin.payments.index') }}" class="{{ $navClass(request()->routeIs('super-admin.payments.*')) }}">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h.01M11 15h2m-8 4h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            Payment Gateways
        </a>
        <a href="{{ route('super-admin.seo.index') }}" class="{{ $navClass(request()->routeIs('super-admin.seo.*')) }}">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 1118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11a3 3 0 100-6 3 3 0 000 6z"/></svg>
            SEO Settings
        </a>

        <p class="sa-sidebar__section-label">Operations</p>
        <a href="{{ route('super-admin.maintenance.index') }}" class="{{ $navClass(request()->routeIs('super-admin.maintenance.*')) }}">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            Maintenance Mode
        </a>
        <a href="{{ route('super-admin.activity-logs.index') }}" class="{{ $navClass(request()->routeIs('super-admin.activity-logs.*')) }}">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10v-4M5 21h14"/></svg>
            Activity Logs
        </a>
        <a href="{{ route('super-admin.support.index') }}" class="{{ $navClass(request()->routeIs('super-admin.support.*')) }}">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8m-8 4h5m5 7l-4-4H6a2 2 0 01-2-2V7a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
            Support System
        </a>
        <a href="{{ route('super-admin.server.index') }}" class="{{ $navClass(request()->routeIs('super-admin.server.*')) }}">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            Server Management
        </a>

        <p class="sa-sidebar__section-label">Advanced Controls</p>
        <a href="{{ route('super-admin.languages.index') }}" class="{{ $navClass(request()->routeIs('super-admin.languages.*')) }}">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1 13l4-9 4 9m-.9-2h-6.2"/></svg>
            Language Management
        </a>
        <a href="{{ route('super-admin.cdn.index') }}" class="{{ $navClass(request()->routeIs('super-admin.cdn.*')) }}">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M5 12h14M7 16h10"/></svg>
            CDN & Cache Controls
        </a>
        <a href="{{ route('super-admin.audit.index') }}" class="{{ $navClass(request()->routeIs('super-admin.audit.*')) }}">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Audit System
        </a>
        <a href="{{ route('super-admin.database.index') }}" class="{{ $navClass(request()->routeIs('super-admin.database.*')) }}">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><ellipse cx="12" cy="6" rx="8" ry="3"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6v6c0 1.7 3.6 3 8 3s8-1.3 8-3V6M4 12v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/></svg>
            Database Management
        </a>
        <a href="{{ route('super-admin.saas.index') }}" class="{{ $navClass(request()->routeIs('super-admin.saas.*')) }}">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l8 4-8 4-8-4 8-4zm8 9l-8 4-8-4m16 5l-8 4-8-4"/></svg>
            SaaS Configuration
        </a>
    </nav>

    <div class="sa-sidebar__footer">
        <div class="sa-sidebar__user">
            <div class="sa-sidebar__avatar">
                {{ strtoupper(substr(auth('super_admin')->user()->name, 0, 1)) }}
            </div>
            <div class="sa-sidebar__user-info">
                <p class="sa-sidebar__user-name">{{ auth('super_admin')->user()->name }}</p>
                <p class="sa-sidebar__user-email">{{ auth('super_admin')->user()->email }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('super-admin.logout') }}">
            @csrf
            <button type="submit" class="sa-sidebar__logout">Sign out</button>
        </form>
    </div>
</aside>
