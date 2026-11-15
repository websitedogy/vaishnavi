<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use App\Models\LoginLog;
use App\Models\Tenant;
use App\Models\UserSession;
use Illuminate\Http\Request;

class SecurityController extends Controller
{
    public function index()
    {
        $blockedIps = BlockedIp::latest()->paginate(8, ['*'], 'blocked_page');
        $loginLogs = LoginLog::latest('attempted_at')->limit(25)->get();

        $failedAttempts = LoginLog::where('is_success', false)
            ->where('attempted_at', '>=', now()->subDays(7))
            ->count();

        $activeSessions = UserSession::with('user.tenant')
            ->whereNull('revoked_at')
            ->latest('last_seen_at')
            ->paginate(10, ['*'], 'session_page');

        return view('super-admin.security.index', compact(
            'blockedIps',
            'loginLogs',
            'failedAttempts',
            'activeSessions'
        ));
    }

    public function blockIp(Request $request)
    {
        $data = $request->validate([
            'ip_address' => ['required', 'string', 'max:45'],
            'reason' => ['nullable', 'string', 'max:255'],
            'blocked_until' => ['nullable', 'date'],
        ]);

        BlockedIp::updateOrCreate(
            ['ip_address' => $data['ip_address']],
            [
                'reason' => $data['reason'] ?? null,
                'blocked_until' => $data['blocked_until'] ?? null,
                'is_active' => true,
            ]
        );

        return back()->with('success', 'IP address blocked successfully.');
    }

    public function unblockIp(BlockedIp $blockedIp)
    {
        $blockedIp->delete();

        return back()->with('success', 'IP address unblocked.');
    }

    public function revokeSession(UserSession $session)
    {
        $session->update(['revoked_at' => now()]);

        return back()->with('success', 'Session revoked.');
    }

    public function lockTenant(Tenant $tenant)
    {
        $tenant->suspend();

        return back()->with('success', 'Tenant locked successfully.');
    }
}
