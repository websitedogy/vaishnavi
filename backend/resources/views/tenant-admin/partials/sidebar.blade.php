@php
    $nav = fn ($active) => 'sa-sidebar__link'.($active ? ' sa-sidebar__link--active' : '');
    $icon = '<svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>';
    $iconSub = '<svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>';
@endphp
<div class="sa-sidebar-overlay" onclick="toggleSidebar()"></div>
<aside class="sa-sidebar">
    <div class="sa-sidebar__brand">
        <div class="sa-sidebar__logo">DN</div>
        <div><p class="sa-sidebar__brand-title">{{ auth('tenant_admin')->user()->tenant->name }}</p><p class="sa-sidebar__brand-subtitle">Tenant Admin</p></div>
    </div>
    <nav class="sa-sidebar__nav">
        <a href="{{ route('tenant-admin.dashboard') }}" class="{{ $nav(request()->routeIs('tenant-admin.dashboard')) }}">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/></svg>
            Dashboard
        </a>
        <a href="{{ auth('tenant_admin')->user()->tenant->publicSiteUrl() }}" target="_blank" class="sa-sidebar__link" style="color:#93c5fd">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            View public site
        </a>
        <p class="sa-sidebar__section-label">Content</p>
        <a href="{{ route('tenant-admin.news.create') }}" class="{{ $nav(request()->routeIs('tenant-admin.news.create')) }}">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Post
        </a>
        <a href="{{ route('tenant-admin.news.index') }}" class="{{ $nav(request()->routeIs('tenant-admin.news.index','tenant-admin.news.edit','tenant-admin.news.show')) }}">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13h2a2 2 0 002-2V9a2 2 0 00-2-2h-2"/></svg>
            Total Posts
        </a>
        <a href="{{ route('tenant-admin.categories.index') }}" class="{{ $nav(request()->routeIs('tenant-admin.categories.*') && !request()->routeIs('tenant-admin.categories.subcategories.*')) }}">
            {!! $icon !!}
            Categories
        </a>
        <a href="{{ route('tenant-admin.categories.subcategories.index') }}" class="{{ $nav(request()->routeIs('tenant-admin.categories.subcategories.*')) }}">
            {!! $iconSub !!}
            Subcategories
        </a>
        <a href="{{ route('tenant-admin.media.index') }}" class="{{ $nav(request()->routeIs('tenant-admin.media.*')) }}">
            <svg class="sa-sidebar__link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16"/></svg>
            Media
        </a>
        <a href="{{ route('tenant-admin.comments.index') }}" class="{{ $nav(request()->routeIs('tenant-admin.comments.*')) }}">Comments</a>
        <a href="{{ route('tenant-admin.breaking.index') }}" class="{{ $nav(request()->routeIs('tenant-admin.breaking.*')) }}">Breaking News</a>
        <p class="sa-sidebar__section-label">Growth</p>
        <a href="{{ route('tenant-admin.ads.index') }}" class="{{ $nav(request()->routeIs('tenant-admin.ads.*')) }}">Advertisements</a>
        <a href="{{ route('tenant-admin.seo.index') }}" class="{{ $nav(request()->routeIs('tenant-admin.seo.*')) }}">SEO</a>
        <a href="{{ route('tenant-admin.social.index') }}" class="{{ $nav(request()->routeIs('tenant-admin.social.*')) }}">Social Media</a>
        <a href="{{ route('tenant-admin.newsletters.index') }}" class="{{ $nav(request()->routeIs('tenant-admin.newsletters.*')) }}">Newsletter</a>
        <a href="{{ route('tenant-admin.push.index') }}" class="{{ $nav(request()->routeIs('tenant-admin.push.*')) }}">Push Notifications</a>
        <a href="{{ route('tenant-admin.polls.index') }}" class="{{ $nav(request()->routeIs('tenant-admin.polls.*')) }}">Polls</a>
        <p class="sa-sidebar__section-label">Business</p>
        <a href="{{ route('tenant-admin.analytics.index') }}" class="{{ $nav(request()->routeIs('tenant-admin.analytics.index')) }}">Analytics</a>
        <a href="{{ route('tenant-admin.monetization.index') }}" class="{{ $nav(request()->routeIs('tenant-admin.monetization.*')) }}">Monetization</a>
        <a href="{{ route('tenant-admin.readers.index') }}" class="{{ $nav(request()->routeIs('tenant-admin.readers.*')) }}">Readers</a>
        <p class="sa-sidebar__section-label">Site & App</p>
        <a href="{{ route('tenant-admin.theme.index') }}" class="{{ $nav(request()->routeIs('tenant-admin.theme.*')) }}">Theme</a>
        <a href="{{ route('tenant-admin.mobile.index') }}" class="{{ $nav(request()->routeIs('tenant-admin.mobile.*')) }}">Mobile App</a>
        <p class="sa-sidebar__section-label">Team & Security</p>
        <a href="{{ route('tenant-admin.staff.index') }}" class="{{ $nav(request()->routeIs('tenant-admin.staff.index','tenant-admin.staff.create','tenant-admin.staff.edit','tenant-admin.staff.profile')) }}">Staff</a>
        <a href="{{ route('tenant-admin.staff.logs') }}" class="{{ $nav(request()->routeIs('tenant-admin.staff.logs')) }}">Activity Logs</a>
        <a href="{{ route('tenant-admin.security.index') }}" class="{{ $nav(request()->routeIs('tenant-admin.security.*')) }}">Security</a>
        <a href="{{ route('tenant-admin.integrations.index') }}" class="{{ $nav(request()->routeIs('tenant-admin.integrations.*')) }}">API & Integrations</a>
    </nav>
    <div class="sa-sidebar__footer">
        <form method="POST" action="{{ route('tenant-admin.logout') }}">@csrf<button type="submit" class="sa-sidebar__logout">Sign out</button></form>
    </div>
</aside>
