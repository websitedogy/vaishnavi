<?php

namespace App\Modules\TenantAdmin\Services;

use App\Models\User;
use App\Modules\SuperAdmin\Models\Tenant;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class StaffService
{
    public function listStaff(int $tenantId): array
    {
        $staff = User::where('tenant_id', $tenantId)
            ->select(['id', 'tenant_id', 'name', 'email', 'role', 'is_active', 'permissions', 'created_at'])
            ->orderBy('id', 'asc')
            ->get();

        return [
            'staff' => $staff,
            'roles' => $this->getAvailableRoles(),
        ];
    }

    public function createStaff(int $tenantId, array $data, ?int $userId = null): User
    {
        $exists = User::where('email', $data['email'])->exists();
        if ($exists) {
            throw ValidationException::withMessages([
                'email' => ['A user with this email address already exists in the system.'],
            ]);
        }

        $role = $data['role'] ?? 'reporter';
        $roleEnum = \App\Enums\UserRole::tryFrom($role) ?? \App\Enums\UserRole::Reporter;
        $permissions = $data['permissions'] ?? $this->getDefaultPermissionsForRole($role);

        $staff = User::create([
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'] ?? 'TenantStaffPass123!',
            'role' => $roleEnum,
            'is_active' => true,
            'permissions' => $permissions,
        ]);

        $roleVal = $staff->role instanceof \BackedEnum ? $staff->role->value : (string)$staff->role;
        TenantAuditHelper::log($tenantId, $userId, 'staff_created', 'staff', "Added new staff member {$staff->name} ({$staff->email}) with role {$roleVal}.");

        return $staff;
    }

    public function updateStaff(int $tenantId, int $staffId, array $data, ?int $userId = null): User
    {
        $staff = User::where('tenant_id', $tenantId)->findOrFail($staffId);

        if (!empty($data['email']) && $data['email'] !== $staff->email) {
            $exists = User::where('email', $data['email'])->where('id', '!=', $staff->id)->exists();
            if ($exists) {
                throw ValidationException::withMessages([
                    'email' => ['This email address is already assigned to another user.'],
                ]);
            }
            $staff->email = $data['email'];
        }

        if (!empty($data['name'])) {
            $staff->name = $data['name'];
        }

        if (!empty($data['role'])) {
            $staff->role = \App\Enums\UserRole::tryFrom($data['role']) ?? \App\Enums\UserRole::Reporter;
            if (!isset($data['permissions'])) {
                $staff->permissions = $this->getDefaultPermissionsForRole($data['role']);
            }
        }

        if (isset($data['permissions'])) {
            $staff->permissions = $data['permissions'];
        }

        if (!empty($data['password'])) {
            $staff->password = $data['password'];
        }

        if (isset($data['is_active'])) {
            $staff->is_active = (bool)$data['is_active'];
        }

        $staff->save();

        TenantAuditHelper::log($tenantId, $userId, 'staff_updated', 'staff', "Updated details for staff member {$staff->name}.");

        return $staff;
    }

    public function deleteStaff(int $tenantId, int $staffId, ?int $currentUserId = null): bool
    {
        if ($staffId === $currentUserId) {
            throw ValidationException::withMessages([
                'staff' => ['You cannot delete your own active staff account.'],
            ]);
        }

        $staff = User::where('tenant_id', $tenantId)->findOrFail($staffId);

        // Ensure at least one admin remains
        $adminCount = User::where('tenant_id', $tenantId)
            ->whereIn('role', ['admin', 'owner'])
            ->where('id', '!=', $staffId)
            ->count();

        if ($adminCount === 0 && ($staff->role === 'admin' || $staff->role === 'owner')) {
            throw ValidationException::withMessages([
                'staff' => ['Cannot delete the only administrative staff member for this tenant.'],
            ]);
        }

        $name = $staff->name;
        $staff->delete();

        TenantAuditHelper::log($tenantId, $currentUserId, 'staff_deleted', 'staff', "Removed staff member {$name}.");

        return true;
    }

    public function getAvailableRoles(): array
    {
        return [
            ['id' => 'admin', 'name' => 'Tenant Administrator', 'description' => 'Full administrative access across all publishing, staff, and configuration modules.'],
            ['id' => 'editor', 'name' => 'Chief / Executive Editor', 'description' => 'Full control over editions, PDF upload, page management, and publishing scheduling.'],
            ['id' => 'reporter', 'name' => 'Staff Reporter', 'description' => 'Read-only access to view editions, media library, and operational reports.'],
        ];
    }

    public function getDefaultPermissionsForRole(string $role): array
    {
        return match ($role) {
            'owner', 'admin' => ['*'],
            'editor' => ['editions.*', 'publishing.*', 'pages.*', 'media.*', 'reports.*'],
            'reporter' => ['editions.view', 'media.view', 'reports.view'],
            default => ['editions.view'],
        };
    }
}
