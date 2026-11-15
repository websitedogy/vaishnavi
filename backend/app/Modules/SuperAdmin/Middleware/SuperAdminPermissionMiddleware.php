<?php

namespace App\Modules\SuperAdmin\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminPermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $admin = $request->user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (!$admin->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'message' => "Forbidden: You do not possess the required permission [{$permission}].",
            ], 403);
        }

        return $next($request);
    }
}
