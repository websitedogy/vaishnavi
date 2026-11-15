<div class="ts-topbar">
    <div class="ts-container ts-topbar__inner">
        <div class="ts-topbar__date">{{ now()->format('l, F j Y') }}</div>
        <div class="ts-topbar__welcome">
            స్వాగతం — {{ $tenant->name }} | Welcome to {{ $tenant->name }}
        </div>
        <nav class="ts-topbar__links" aria-label="Utility links">
            <a href="{{ route('tenant-site.home', $tenant->slug) }}#about">About Us</a>
            <a href="{{ route('tenant-site.home', $tenant->slug) }}#contact">Contact Us</a>
            <a href="{{ route('tenant-site.home', $tenant->slug) }}#privacy">Privacy Policy</a>
            <a href="{{ route('tenant-site.home', $tenant->slug) }}#terms">Terms</a>
        </nav>
    </div>
</div>
