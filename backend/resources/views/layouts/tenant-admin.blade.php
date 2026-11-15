<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ auth('tenant_admin')->user()?->tenant?->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{brand:{500:'#3b82f6',600:'#2563eb',700:'#1d4ed8'}}}}}</script>
    <link rel="stylesheet" href="{{ asset('css/super-admin/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/super-admin/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/super-admin/navbar.css') }}">
    @stack('styles')
</head>
<body class="h-full bg-slate-100 text-slate-800 antialiased">
    <div id="app" class="sa-app">
        @include('tenant-admin.partials.sidebar')
        <div class="sa-main-column">
            @include('tenant-admin.partials.navbar')
            <main class="sa-main-content">
                @if (session('success'))<div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">{{ session('success') }}</div>@endif
                @if (session('error'))<div class="mb-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 text-sm">{{ session('error') }}</div>@endif
                @yield('content')
            </main>
        </div>
    </div>
    <script src="{{ asset('js/super-admin/layout.js') }}"></script>
    @stack('scripts')
</body>
</html>
