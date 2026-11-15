<?php

namespace App\Modules\SuperAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SuperAdmin\Models\SuperAdmin;
use App\Shared\Traits\ApiResponseTrait;
use App\Shared\Helpers\AuditHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    use ApiResponseTrait;

    public function getProfile(Request $request)
    {
        $admin = $request->user();
        if (!$admin) {
            return $this->errorResponse('Unauthenticated.', 401);
        }

        $admin->load('role.permissions');
        $sessions = DB::table('super_admin_sessions')
            ->where('super_admin_id', $admin->id)
            ->orderByDesc('last_activity')
            ->limit(10)
            ->get();

        return $this->successResponse([
            'profile' => $admin,
            'sessions' => $sessions,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $admin = $request->user();
        if (!$admin) {
            return $this->errorResponse('Unauthenticated.', 401);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'avatar' => 'nullable|string|max:500',
        ]);

        $admin->update($validated);
        AuditHelper::log($admin->id, 'PROFILE_UPDATED', 'SuperAdmin', $admin->id, "Profile information updated.");

        return $this->successResponse($admin, 'Profile updated successfully.');
    }

    public function changePassword(Request $request)
    {
        $admin = $request->user();
        if (!$admin) {
            return $this->errorResponse('Unauthenticated.', 401);
        }

        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|different:current_password',
        ]);

        if (!Hash::check($request->current_password, $admin->password)) {
            return $this->errorResponse('Current password does not match.', 422);
        }

        $admin->update([
            'password' => Hash::make($request->new_password),
        ]);

        AuditHelper::log($admin->id, 'PASSWORD_CHANGED', 'SuperAdmin', $admin->id, "Admin password changed.");

        return $this->successResponse(null, 'Password successfully updated.');
    }
}
