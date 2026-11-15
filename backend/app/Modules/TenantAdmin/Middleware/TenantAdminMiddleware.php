<?php

namespace App\Modules\TenantAdmin\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use App\Modules\SuperAdmin\Models\Tenant;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class TenantAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Missing bearer token for Tenant Admin.',
            ], 401);
        }

        $user = null;

        // Support development/test tokens ONLY in non-production environments
        if (app()->environment(['local', 'testing', 'development'])) {
            if ($token === 'test-tenant-admin-token') {
                $user = User::where('email', 'owner@samacharam.com')->first();
            } elseif ($token === 'test-tenant-editor-token') {
                $user = User::where('email', 'editor@samacharam.com')->first();
            } elseif ($token === 'test-tenant-2-token') {
                $user = User::where('email', 'admin@andhraprabha.com')->first();
            }
        }

        // Standard Sanctum Bearer Token resolution
        if (!$user) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable_type === User::class) {
                $user = $accessToken->tokenable;
                $user->withAccessToken($accessToken);
            }
        }

        if (!$user || !$user->tenant_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid token or user is not assigned to any tenant.',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: Your staff account has been deactivated.',
            ], 403);
        }

        // Resolve Tenant
        $tenant = Tenant::find($user->tenant_id);
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant record not found.',
            ], 404);
        }

        // Check Tenant Status (Active vs Suspended / Expired)
        if ($tenant->status === 'suspended') {
            return response()->json([
                'success' => false,
                'message' => 'Tenant account is currently suspended by platform administration.',
            ], 403);
        }

        if ($tenant->status === 'expired' || $tenant->status === 'archived') {
            return response()->json([
                'success' => false,
                'message' => 'Tenant subscription has expired or been archived.',
            ], 403);
        }

        // Set auth user and request tenant context
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        auth()->setUser($user);

        // Bind active tenant context (Strictly server-side authoritative)
        $request->attributes->set('tenant', $tenant);
        $request->attributes->set('tenant_id', $tenant->id);

        return $next($request);
    }
}
