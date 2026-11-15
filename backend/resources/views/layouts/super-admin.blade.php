<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — News CMS Super Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 50: '#eff6ff', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8', 900: '#1e3a5f' }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="{{ asset('css/super-admin/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/super-admin/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/super-admin/navbar.css') }}">
    <style>[x-cloak] { display: none !important; }</style>
    @stack('styles')
</head>
<body class="h-full bg-slate-100 text-slate-800 antialiased">
    <div id="app" class="sa-app">
        @include('super-admin.partials.sidebar')

        <div class="sa-main-column">
            @include('super-admin.partials.navbar')

            <main class="sa-main-content">
                @include('super-admin.partials.alerts')
                @yield('content')
            </main>
        </div>
    </div>

    <script src="{{ asset('js/super-admin/layout.js') }}"></script>
    @stack('scripts')
</body>
</html>
