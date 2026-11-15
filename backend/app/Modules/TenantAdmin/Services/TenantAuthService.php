<?php

namespace App\Modules\TenantAdmin\Services;

use App\Models\User;
use App\Modules\SuperAdmin\Models\Tenant;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class TenantAuthService
{
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid email credentials.'],
            ]);
        }

        if (!$user->tenant_id) {
            throw ValidationException::withMessages([
                'email' => ['This account is not associated with any publishing tenant.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your staff account has been deactivated. Please contact your tenant administrator.'],
            ]);
        }

        $tenant = Tenant::find($user->tenant_id);
        if (!$tenant || $tenant->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['The tenant newspaper account is currently not active (' . ($tenant->status ?? 'unknown') . ').'],
            ]);
        }

        // Issue token
        $token = $user->createToken('tenant-staff-token', ['tenant-admin'])->plainTextToken;

        TenantAuditHelper::log($tenant->id, $user->id, 'login', 'auth', "Staff member {$user->name} logged in.");

        return [
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'tenant_id' => $tenant->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'permissions' => is_array($user->permissions) ? $user->permissions : json_decode($user->permissions ?? '[]', true),
            ],
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'domain' => $tenant->domain,
                'status' => $tenant->status,
                'subscription' => $tenant->subscription,
            ],
        ];
    }

    public function logout($user): void
    {
        if (method_exists($user, 'currentAccessToken') && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        } elseif (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }
    }

    public function getMe($user): array
    {
        $tenant = Tenant::find($user->tenant_id);
        $profile = \App\Modules\TenantAdmin\Models\NewspaperProfile::where('tenant_id', $user->tenant_id)->first();

        return [
            'user' => [
                'id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'permissions' => is_array($user->permissions) ? $user->permissions : json_decode($user->permissions ?? '[]', true),
            ],
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'domain' => $tenant->domain,
                'status' => $tenant->status,
                'subscription' => $tenant->subscription,
                'storage_limit_mb' => $tenant->storage_limit_mb,
                'storage_used_mb' => $tenant->storage_used_mb,
            ],
            'profile' => $profile,
        ];
    }

    public function changePassword($user, string $currentPassword, string $newPassword): bool
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password entered is incorrect.'],
            ]);
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        TenantAuditHelper::log($user->tenant_id, $user->id, 'password_change', 'auth', "Staff member changed password.");

        return true;
    }
}
