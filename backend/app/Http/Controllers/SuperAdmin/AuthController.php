<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use App\Models\LoginLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('super-admin.auth.login');
    }

    /**
     * Handle a login request.
     */
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

        if (Auth::guard('super_admin')->attempt([
            'email' => $request->email,
            'password' => $request->password,
            'is_active' => true,
        ], $request->boolean('remember'))) {
            LoginLog::create([
                'super_admin_id' => Auth::guard('super_admin')->id(),
                'email' => $request->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'is_success' => true,
                'guard' => 'super_admin',
                'attempted_at' => now(),
            ]);

            $request->session()->regenerate();
            return redirect()->intended(route('super-admin.dashboard'));
        }

        LoginLog::create([
            'email' => $request->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'is_success' => false,
            'guard' => 'super_admin',
            'attempted_at' => now(),
        ]);

        throw ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }

    /**
     * Handle a logout request.
     */
    public function logout(Request $request)
    {
        Auth::guard('super_admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('super-admin.login');
    }
}
