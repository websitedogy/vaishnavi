<?php

namespace App\Http\Controllers\TenantAdmin;

use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('tenant-admin.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (BlockedIp::where('ip_address', $request->ip())->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Your IP is temporarily blocked.',
            ]);
        }

        $user = User::with('tenant')
            ->where('email', $request->email)
            ->whereNotNull('tenant_id')
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            $this->logAttempt($request, false);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if (! $user->canAccessTenantPanel()) {
            $this->logAttempt($request, false, $user);

            $message = match (true) {
                ! $user->is_active => 'Your account is inactive. Contact platform support.',
                ! $user->isTenantAdmin() => 'Only tenant administrators can sign in here.',
                $user->tenant?->isSuspended() => 'This news site is suspended. Contact platform support.',
                default => 'Your subscription is inactive. Renew to continue.',
            };

            throw ValidationException::withMessages([
                'email' => $message,
            ]);
        }

        Auth::guard('tenant_admin')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->put('tenant_id', $user->tenant_id);

        $this->logAttempt($request, true, $user);

        return redirect()->intended(route('tenant-admin.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('tenant_admin')->logout();
        $request->session()->forget('tenant_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('tenant-admin.login');
    }

    protected function logAttempt(Request $request, bool $success, ?User $user = null): void
    {
        LoginLog::create([
            'user_id' => $user?->id,
            'email' => $request->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'is_success' => $success,
            'guard' => 'tenant_admin',
            'attempted_at' => now(),
        ]);
    }
}
