<?php

namespace App\Modules\SuperAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SuperAdmin\Models\SuperAdmin;
use App\Modules\SuperAdmin\Models\SuperAdminRole;
use App\Modules\SuperAdmin\Models\SuperAdminPermission;
use App\Shared\Traits\ApiResponseTrait;
use App\Shared\Helpers\AuditHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SecurityController extends Controller
{
    use ApiResponseTrait;

    public function admins(Request $request)
    {
        $admins = SuperAdmin::with('role')->paginate($request->integer('per_page', 15));
        return $this->successResponse($admins);
    }

    public function storeAdmin(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:super_admins,email',
            'phone' => 'nullable|string|max:30',
            'password' => 'required|string|min:8',
            'role_id' => 'required|integer|exists:super_admin_roles,id',
        ]);

        $admin = SuperAdmin::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
            'is_active' => true,
            'status' => 'active',
        ]);

        $currentAdmin = $request->user();
        AuditHelper::log($currentAdmin ? $currentAdmin->id : null, 'ADMIN_CREATED', 'SuperAdmin', $admin->id, "New admin account created: {$admin->email}.");

        return $this->successResponse($admin->load('role'), 'Admin account created successfully.', 201);
    }

    public function updateAdmin(Request $request, int $id)
    {
        $admin = SuperAdmin::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'role_id' => 'sometimes|required|integer|exists:super_admin_roles,id',
            'is_active' => 'nullable|boolean',
            'status' => 'nullable|string|in:active,suspended',
        ]);

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($request->password);
        }

        $admin->update($validated);

        $currentAdmin = $request->user();
        AuditHelper::log($currentAdmin ? $currentAdmin->id : null, 'ADMIN_UPDATED', 'SuperAdmin', $admin->id, "Admin account updated: {$admin->email}.");

        return $this->successResponse($admin->load('role'), 'Admin account updated.');
    }

    public function roles()
    {
        $roles = SuperAdminRole::with('permissions')->get();
        return $this->successResponse($roles);
    }

    public function permissions()
    {
        $permissions = SuperAdminPermission::all()->groupBy('module');
        return $this->successResponse($permissions);
    }

    public function sessions(Request $request)
    {
        $sessions = DB::table('super_admin_sessions')
            ->join('super_admins', 'super_admin_sessions.super_admin_id', '=', 'super_admins.id')
            ->select('super_admin_sessions.*', 'super_admins.name as admin_name', 'super_admins.email as admin_email')
            ->orderByDesc('super_admin_sessions.last_activity')
            ->limit(30)
            ->get();

        return $this->successResponse($sessions);
    }

    public function revokeSession(Request $request, int $id)
    {
        DB::table('super_admin_sessions')->where('id', $id)->delete();
        $currentAdmin = $request->user();
        AuditHelper::log($currentAdmin ? $currentAdmin->id : null, 'SESSION_REVOKED', 'SuperAdminSession', $id, "Admin session #{$id} revoked.");

        return $this->successResponse(null, 'Session revoked.');
    }

    public function auditLogs(Request $request)
    {
        $query = DB::table('audit_logs')
            ->leftJoin('super_admins', 'audit_logs.super_admin_id', '=', 'super_admins.id')
            ->select('audit_logs.*', 'super_admins.name as actor_name', 'super_admins.email as actor_email');

        if ($request->filled('action')) {
            $query->where('audit_logs.action', 'like', "%{$request->action}%");
        }

        if ($request->filled('entity_type')) {
            $query->where('audit_logs.entity_type', $request->entity_type);
        }

        $logs = $query->orderByDesc('audit_logs.performed_at')->paginate($request->integer('per_page', 20));

        return $this->successResponse($logs);
    }

    public function loginLogs(Request $request)
    {
        $logs = DB::table('super_admin_login_logs')
            ->orderByDesc('attempted_at')
            ->paginate($request->integer('per_page', 20));

        return $this->successResponse($logs);
    }
}
