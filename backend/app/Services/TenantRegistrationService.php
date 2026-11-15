<?php

namespace App\Services;

use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TenantRegistrationService
{
    public function register(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {
            $tenant = Tenant::create([
                'name' => $data['company_name'],
                'slug' => Tenant::generateSlug($data['company_name']),
                'email' => $data['company_email'],
                'domain' => null,
                'subscription' => $data['subscription'] ?? 'basic',
                'subscription_amount' => $this->planAmount($data['subscription'] ?? 'basic'),
                'status' => TenantStatus::Trial,
                'trial_ends_at' => now()->addDays(14),
            ]);

            User::create([
                'tenant_id' => $tenant->id,
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
                'role' => UserRole::Admin,
                'is_active' => true,
                'permissions' => UserRole::Admin->defaultPermissions(),
            ]);

            ActivityLogger::log(
                'tenant_registered',
                'Public tenant registration',
                "{$tenant->name} signed up via the marketing site.",
                $tenant
            );

            return $tenant;
        });
    }

    protected function planAmount(string $plan): float
    {
        return match ($plan) {
            'standard' => 79,
            'premium' => 149,
            'enterprise' => 299,
            default => 29,
        };
    }
}
