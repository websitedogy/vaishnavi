<?php

namespace App\Modules\PublicView\Middleware;

use App\Modules\PublicView\Services\PublicTenantService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolvePublicTenant
{
    protected PublicTenantService $tenantService;

    public function __construct(PublicTenantService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    /**
     * Handle an incoming request and resolve the public tenant context.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('tenantSlug');
        $tenant = $this->tenantService->resolveFromRequest($request, $slug);

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Publication not found.',
            ], 404);
        }

        $request->attributes->set('public_tenant', $tenant);

        return $next($request);
    }
}
