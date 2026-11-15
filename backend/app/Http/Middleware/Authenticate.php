<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        if ($request->is('tenant-admin') || $request->is('tenant-admin/*')) {
            return route('tenant-admin.login');
        }

        if ($request->is('super-admin') || $request->is('super-admin/*')) {
            return route('super-admin.login');
        }

        return route('super-admin.login');
    }
}
