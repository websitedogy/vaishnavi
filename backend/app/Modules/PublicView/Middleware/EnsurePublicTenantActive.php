<?php

namespace App\Modules\PublicView\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePublicTenantActive
{
    /**
     * Ensure the resolved public tenant is currently active and not suspended.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->attributes->get('public_tenant');

        if (!$tenant || $tenant->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Publication is currently suspended or inactive.',
            ], 403);
        }

        return $next($request);
    }
}
