<?php

namespace Database\Seeders;

use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\NewsPost;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        if (Tenant::exists()) {
            return;
        }

        $tenants = [
            [
                'name' => 'Telugu Daily News',
                'email' => 'contact@telugudaily.com',
                'domain' => 'telugudaily.news_saas.test',
                'subscription' => 'premium',
                'subscription_amount' => 149,
                'status' => TenantStatus::Active,
            ],
            [
                'name' => 'Andhra Express',
                'email' => 'admin@andhraexpress.com',
                'domain' => 'andhraexpress.news_saas.test',
                'subscription' => 'standard',
                'subscription_amount' => 79,
                'status' => TenantStatus::Trial,
                'trial_ends_at' => now()->addDays(14),
            ],
            [
                'name' => 'Hyderabad Herald',
                'email' => 'info@hydherald.com',
                'domain' => null,
                'subscription' => 'basic',
                'subscription_amount' => 29,
                'status' => TenantStatus::Suspended,
                'suspended_at' => now()->subDays(2),
            ],
            [
                'name' => 'Coastal Chronicle',
                'email' => 'news@coastalchronicle.com',
                'domain' => 'coastal.news_saas.test',
                'subscription' => 'enterprise',
                'subscription_amount' => 299,
                'status' => TenantStatus::Expired,
                'subscription_ends_at' => now()->subDays(5),
            ],
        ];

        foreach ($tenants as $data) {
            $tenant = Tenant::create([
                ...$data,
                'slug' => Tenant::generateSlug($data['name']),
            ]);

            User::create([
                'tenant_id' => $tenant->id,
                'name' => $tenant->name.' Admin',
                'email' => 'admin@'.str_replace(' ', '', strtolower($tenant->name)).'.local',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'is_active' => $tenant->status !== TenantStatus::Suspended,
            ]);

            for ($i = 1; $i <= rand(3, 12); $i++) {
                NewsPost::create([
                    'tenant_id' => $tenant->id,
                    'title' => "Sample article {$i} — {$tenant->name}",
                    'status' => $i % 3 === 0 ? 'draft' : 'published',
                    'views' => rand(50, 5000),
                ]);
            }
        }

        ActivityLogger::log('system', 'Platform seeded', 'Demo tenants and news posts were created.');
    }
}
