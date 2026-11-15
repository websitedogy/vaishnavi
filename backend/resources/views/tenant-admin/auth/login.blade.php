<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Tenant Admin Login — News CMS</title>
    <link rel="stylesheet" href="{{ asset('css/tenant-admin/login.css') }}">
</head>
<body>
    <div class="ta-login-page">
        <div class="ta-login-card">
            <div class="ta-login-brand">
                <div class="ta-login-logo">DN</div>
                <h1 class="ta-login-title">Tenant Admin Login</h1>
                <p class="ta-login-subtitle">Sign in with your registration email & password</p>
            </div>

            @if (session('success'))
                <div class="ta-login-success">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('tenant-admin.login.submit') }}">
                @csrf

                <div class="ta-login-field">
                    <label for="email" class="ta-login-label">Email address</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email', session('registered_email', config('app.env') === 'local' ? 'tenantadmin@news.local' : '')) }}"
                        required
                        autofocus
                        class="ta-login-input"
                        placeholder="tenantadmin@news.local"
                    >
                    @error('email')
                        <p class="ta-login-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ta-login-field">
                    <label for="password" class="ta-login-label">Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        class="ta-login-input"
                        placeholder="••••••••"
                    >
                    @error('password')
                        <p class="ta-login-error">{{ $message }}</p>
                    @enderror
                </div>

                <label class="ta-login-remember">
                    <input type="checkbox" name="remember" value="1">
                    Remember me
                </label>

                <button type="submit" class="ta-login-submit">Sign in</button>
            </form>

            @if (config('app.env') === 'local')
                <div class="ta-login-demo">
                    <strong>Demo credentials</strong> (after <code>php artisan db:seed --class=TenantAdminSeeder</code>):<br>
                    Email: <code>tenantadmin@news.local</code><br>
                    Password: <code>password</code>
                </div>
            @endif

            <a href="{{ route('home') }}" class="ta-login-link">← Register another site</a>

            <p class="ta-login-footer">
                © {{ date('Y') }} News CMS · Tenant panel uses MySQL (<code>{{ config('database.connections.'.config('database.default').'.database') }}</code>)
            </p>
        </div>
    </div>
</body>
</html>
