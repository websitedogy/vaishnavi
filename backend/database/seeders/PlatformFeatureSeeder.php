<?php

namespace Database\Seeders;

use App\Enums\AdPlacement;
use App\Enums\SslStatus;
use App\Enums\UserRole;
use App\Models\Advertisement;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\TenantTheme;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlatformFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $baseDomain = config('platform.base_domain');

        User::whereNotNull('tenant_id')->where('email', 'like', 'admin@%')->update(['role' => UserRole::Admin]);

        foreach (Tenant::all() as $tenant) {
            if (! $tenant->domains()->where('type', 'subdomain')->exists()) {
                TenantDomain::create([
                    'tenant_id' => $tenant->id,
                    'type' => 'subdomain',
                    'host' => $tenant->slug.'.'.$baseDomain,
                    'is_primary' => true,
                    'ssl_auto' => true,
                    'ssl_status' => SslStatus::Active,
                    'ssl_issued_at' => now()->subDays(10),
                    'ssl_expires_at' => now()->addMonths(3),
                ]);
            }

            if ($tenant->domain && ! $tenant->domains()->where('host', $tenant->domain)->exists()) {
                TenantDomain::create([
                    'tenant_id' => $tenant->id,
                    'type' => 'custom',
                    'host' => $tenant->domain,
                    'is_primary' => false,
                    'ssl_auto' => true,
                    'ssl_status' => SslStatus::Active,
                    'ssl_issued_at' => now()->subDays(5),
                    'ssl_expires_at' => now()->addMonths(3),
                ]);
            }

            $tenant->theme()->updateOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'theme_preset' => ['classic', 'modern', 'magazine', 'bold'][array_rand(['classic', 'modern', 'magazine', 'bold'])],
                    'layout' => 'grid',
                    'font_family' => 'Inter',
                    'primary_color' => '#2563eb',
                    'secondary_color' => '#1e293b',
                    'accent_color' => '#f59e0b',
                    'features' => [
                        'live_tv' => rand(0, 1) === 1,
                        'video_section' => true,
                        'notifications' => true,
                        'polls' => rand(0, 1) === 1,
                    ],
                ]
            );

            if ($tenant->users()->where('role', UserRole::Editor)->count() === 0) {
                User::create([
                    'tenant_id' => $tenant->id,
                    'name' => $tenant->name.' Editor',
                    'email' => 'editor@'.$tenant->slug.'.local',
                    'password' => Hash::make('password'),
                    'role' => UserRole::Editor,
                    'is_active' => true,
                ]);
            }

            if ($tenant->users()->where('role', UserRole::Reporter)->count() === 0) {
                User::create([
                    'tenant_id' => $tenant->id,
                    'name' => $tenant->name.' Reporter',
                    'email' => 'reporter@'.$tenant->slug.'.local',
                    'password' => Hash::make('password'),
                    'role' => UserRole::Reporter,
                    'is_active' => true,
                ]);
            }
        }

        if (Advertisement::count() === 0) {
            foreach (AdPlacement::cases() as $placement) {
                Advertisement::create([
                    'tenant_id' => null,
                    'placement' => $placement,
                    'name' => 'Global '.$placement->label(),
                    'advertiser' => 'News CMS Network',
                    'content' => 'Sample global advertisement',
                    'is_active' => true,
                    'impressions' => rand(5000, 50000),
                    'clicks' => rand(100, 2000),
                    'revenue' => rand(200, 1500),
                ]);
            }

            foreach (Tenant::limit(2)->get() as $tenant) {
                Advertisement::create([
                    'tenant_id' => $tenant->id,
                    'placement' => AdPlacement::Sidebar,
                    'name' => $tenant->name.' Sidebar Promo',
                    'advertiser' => 'Local Sponsor',
                    'is_active' => true,
                    'impressions' => rand(1000, 10000),
                    'clicks' => rand(50, 500),
                    'revenue' => rand(50, 400),
                ]);
            }
        }
    }
}
