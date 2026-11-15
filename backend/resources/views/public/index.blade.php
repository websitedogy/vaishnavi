<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Doggy SaaS News — launch your regional news website with a free trial. Register and manage your site from the tenant admin panel.">
    <title>Doggy SaaS News — Launch your news site</title>
    <link rel="stylesheet" href="{{ asset('css/public/landing.css') }}">
</head>
<body class="lp-body">
    @include('public.partials.navbar')
    @include('public.partials.hero')
    @include('public.partials.register')
    @include('public.partials.faqs')
    @include('public.partials.footer')
</body>
</html>
