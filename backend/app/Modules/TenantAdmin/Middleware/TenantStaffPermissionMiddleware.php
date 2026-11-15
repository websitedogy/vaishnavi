<?php

namespace App\Modules\TenantAdmin\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantStaffPermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        // Owner and Admin have unrestricted access
        if ($user->role === 'owner' || $user->role === 'admin') {
            return $next($request);
        }

        // Check granular permission
        $permissions = is_array($user->permissions) ? $user->permissions : json_decode($user->permissions ?? '[]', true);

        if (in_array('*', $permissions, true)) {
            return $next($request);
        }

        foreach ($permissions as $p) {
            if ($p === $permission || (str_ends_with($p, '.*') && str_starts_with($permission, substr($p, 0, -2)))) {
                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'message' => "Forbidden: You lack the required permission [{$permission}] for this operation.",
        ], 403);
    }
}
