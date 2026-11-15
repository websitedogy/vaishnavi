<?php

namespace Database\Seeders;

use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantAdminSeeder extends Seeder
{
    /**
     * Demo tenant admin (uses main app DB from .env).
     */
    public function run(): void
    {
        $tenant = Tenant::query()
            ->where('status', TenantStatus::Active)
            ->orderBy('id')
            ->first();

        if (! $tenant) {
            $tenant = Tenant::create([
                'name' => 'Demo News Site',
                'slug' => 'demo-news',
                'email' => 'contact@demo.news',
                'domain' => 'demo.news_saas.test',
                'subscription' => 'standard',
                'subscription_amount' => 79,
                'status' => TenantStatus::Active,
            ]);
        }

        User::updateOrCreate(
            ['email' => 'tenantadmin@news.local'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Demo Tenant Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'is_active' => true,
                'permissions' => UserRole::Admin->defaultPermissions(),
            ]
        );
    }
}
