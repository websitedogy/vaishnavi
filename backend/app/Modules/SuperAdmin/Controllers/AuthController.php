<?php

namespace App\Modules\SuperAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SuperAdmin\Models\SuperAdmin;
use App\Shared\Traits\ApiResponseTrait;
use App\Shared\Helpers\AuditHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $admin = SuperAdmin::with('role.permissions')->where('email', $request->email)->first();
        $ip = $request->ip();
        $ua = substr($request->userAgent() ?? '', 0, 500);

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            DB::table('super_admin_login_logs')->insert([
                'email' => $request->email,
                'ip_address' => $ip,
                'user_agent' => $ua,
                'status' => 'failed',
                'failure_reason' => 'Invalid credentials',
                'attempted_at' => Carbon::now(),
            ]);

            return $this->errorResponse('Invalid credentials provided.', 401);
        }

        if (!$admin->is_active || $admin->status === 'suspended') {
            DB::table('super_admin_login_logs')->insert([
                'super_admin_id' => $admin->id,
                'email' => $request->email,
                'ip_address' => $ip,
                'user_agent' => $ua,
                'status' => 'blocked',
                'failure_reason' => 'Account inactive or suspended',
                'attempted_at' => Carbon::now(),
            ]);

            return $this->errorResponse('This administrator account is suspended or inactive.', 403);
        }

        // Update login stats
        $admin->update([
            'last_login_at' => Carbon::now(),
            'last_login_ip' => $ip,
        ]);

        // Create Sanctum Token
        $tokenResult = $admin->createToken('super-admin-control-center-token');
        $token = $tokenResult->plainTextToken;

        // Log session
        DB::table('super_admin_sessions')->insert([
            'super_admin_id' => $admin->id,
            'ip_address' => $ip,
            'user_agent' => $ua,
            'token_id' => (string) $tokenResult->accessToken->id,
            'is_current' => true,
            'last_activity' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Login log
        DB::table('super_admin_login_logs')->insert([
            'super_admin_id' => $admin->id,
            'email' => $admin->email,
            'ip_address' => $ip,
            'user_agent' => $ua,
            'status' => 'success',
            'failure_reason' => null,
            'attempted_at' => Carbon::now(),
        ]);

        AuditHelper::log($admin->id, 'SUPER_ADMIN_LOGIN', 'SuperAdmin', $admin->id, "Super Admin '{$admin->name}' logged in successfully.", null, $ip, $ua);

        return $this->successResponse([
            'token' => $token,
            'user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'phone' => $admin->phone,
                'avatar' => $admin->avatar,
                'role' => $admin->role ? $admin->role->name : 'Super Admin',
                'role_slug' => $admin->role ? $admin->role->slug : 'super_admin',
                'permissions' => $admin->role ? $admin->role->permissions->pluck('slug') : [],
                'mfa_enabled' => $admin->mfa_enabled,
                'last_login_at' => $admin->last_login_at,
            ]
        ], 'Authenticated successfully.');
    }

    public function me(Request $request)
    {
        $admin = $request->user();
        if (!$admin) {
            return $this->errorResponse('Unauthenticated.', 401);
        }

        $admin->load('role.permissions');

        return $this->successResponse([
            'id' => $admin->id,
            'name' => $admin->name,
            'email' => $admin->email,
            'phone' => $admin->phone,
            'avatar' => $admin->avatar,
            'role' => $admin->role ? $admin->role->name : 'Super Admin',
            'role_slug' => $admin->role ? $admin->role->slug : 'super_admin',
            'permissions' => $admin->role ? $admin->role->permissions->pluck('slug') : [],
            'mfa_enabled' => $admin->mfa_enabled,
            'last_login_at' => $admin->last_login_at,
        ]);
    }

    public function logout(Request $request)
    {
        $admin = $request->user();
        if ($admin && method_exists($admin, 'currentAccessToken') && $admin->currentAccessToken()) {
            $admin->currentAccessToken()->delete();
        }

        if ($admin) {
            AuditHelper::log($admin->id, 'SUPER_ADMIN_LOGOUT', 'SuperAdmin', $admin->id, "Super Admin '{$admin->name}' logged out.");
        }

        return $this->successResponse(null, 'Logged out successfully.');
    }
}
