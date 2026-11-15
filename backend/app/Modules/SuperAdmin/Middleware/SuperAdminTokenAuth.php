<?php

namespace App\Modules\SuperAdmin\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Modules\SuperAdmin\Models\SuperAdmin;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Missing bearer token.',
            ], 401);
        }

        // Support personal_access_tokens from Sanctum or seeded dev token ONLY in non-production
        $admin = null;
        if (app()->environment(['local', 'testing', 'development'])) {
            if ($token === 'superadmin-master-session-token' || $token === 'test-super-admin-token') {
                $admin = SuperAdmin::with('role.permissions')->where('is_active', true)->first();
            }
        }
        
        if (!$admin) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable_type === SuperAdmin::class) {
                $admin = $accessToken->tokenable;
            }
        }

        if (!$admin || !$admin->is_active || $admin->status === 'suspended') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid token or account suspended.',
            ], 401);
        }

        $request->setUserResolver(function () use ($admin) {
            return $admin;
        });

        // Set auth guard user
        auth()->setUser($admin);

        return $next($request);
    }
}
