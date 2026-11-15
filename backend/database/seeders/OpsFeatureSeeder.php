<?php

namespace Database\Seeders;

use App\Models\BackupRecord;
use App\Models\GlobalNotification;
use App\Models\LoginLog;
use App\Models\ApiKey;
use App\Models\ApiUsageLog;
use App\Models\PaymentGateway;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\TenantTrafficStat;
use App\Models\User;
use App\Models\UserSession;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\AuditLog;
use App\Services\ActivityLogger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OpsFeatureSeeder extends Seeder
{
    public function run(): void
    {
        if (Tenant::count() === 0) {
            return;
        }

        foreach (Tenant::all() as $tenant) {
            foreach (range(1, 14) as $daysAgo) {
                TenantTrafficStat::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'date' => now()->subDays($daysAgo)->toDateString(),
                    ],
                    [
                        'visitors' => random_int(200, 4000),
                        'page_views' => random_int(500, 10000),
                        'top_news_views' => random_int(50, 3000),
                    ]
                );
            }
        }

        if (LoginLog::count() < 40) {
            foreach (range(1, 50) as $i) {
                LoginLog::create([
                    'email' => 'user'.$i.'@example.com',
                    'ip_address' => '192.168.0.'.random_int(10, 240),
                    'user_agent' => 'Browser '.$i,
                    'is_success' => $i % 4 !== 0,
                    'guard' => 'web',
                    'attempted_at' => now()->subMinutes($i * 25),
                ]);
            }
        }

        if (UserSession::count() === 0) {
            User::whereNotNull('tenant_id')->limit(20)->get()->each(function (User $user) {
                UserSession::create([
                    'user_id' => $user->id,
                    'session_token' => Str::random(50),
                    'ip_address' => '10.0.0.'.random_int(2, 200),
                    'user_agent' => 'Tenant browser',
                    'last_seen_at' => now()->subMinutes(random_int(1, 120)),
                ]);
            });
        }

        if (BackupRecord::count() === 0) {
            foreach (['database', 'files', 'full'] as $type) {
                BackupRecord::create([
                    'type' => $type,
                    'file_path' => 'backups/sample_'.$type.'_'.now()->format('Ymd').'.zip',
                    'file_size' => random_int(50, 900) * 1024 * 1024,
                    'status' => 'ready',
                    'backup_taken_at' => now()->subDays(random_int(1, 10)),
                    'notes' => ucfirst($type).' backup',
                ]);
            }
        }

        if (GlobalNotification::count() === 0) {
            GlobalNotification::create([
                'type' => 'announcement',
                'title' => 'New platform release',
                'message' => 'A new analytics panel is now available for all tenants.',
                'channels' => ['in_app', 'email'],
                'is_sent' => true,
                'sent_at' => now()->subDays(1),
            ]);

            GlobalNotification::create([
                'type' => 'expiry_alert',
                'title' => 'Subscription expiry reminder',
                'message' => 'Please renew your subscription before the due date.',
                'channels' => ['email'],
                'is_sent' => true,
                'sent_at' => now()->subDays(2),
            ]);
        }

        PaymentGateway::updateOrCreate(
            ['name' => 'Razorpay'],
            ['is_enabled' => true, 'mode' => 'test', 'merchant_key' => 'rzp_test_key', 'secret_key' => 'rzp_test_secret']
        );

        PaymentGateway::updateOrCreate(
            ['name' => 'Cashfree'],
            ['is_enabled' => false, 'mode' => 'test', 'merchant_key' => 'cf_test_key', 'secret_key' => 'cf_test_secret']
        );

        $defaults = [
            'storage.image_upload_size_mb' => ['storage', '5'],
            'storage.video_storage_limit_gb' => ['storage', '50'],
            'storage.tenant_disk_limit_gb' => ['storage', '100'],
            'seo.sitemap_url' => ['seo', url('/sitemap.xml')],
            'seo.robots_txt' => ['seo', "User-agent: *\nAllow: /\nSitemap: ".url('/sitemap.xml')],
            'seo.meta_title_default' => ['seo', 'News SaaS Platform'],
            'seo.meta_description_default' => ['seo', 'Multi-tenant news platform for digital publishers.'],
        ];

        foreach ($defaults as $key => [$group, $value]) {
            PlatformSetting::updateOrCreate(['key' => $key], ['group' => $group, 'value' => $value]);
        }

        if (ApiKey::count() === 0) {
            $globalKey = ApiKey::create([
                'name' => 'Global Reporting Key',
                'key' => 'nsaas_'.Str::random(40),
                'is_active' => true,
                'expires_at' => now()->addMonths(6),
            ]);

            ApiUsageLog::create([
                'api_key_id' => $globalKey->id,
                'endpoint' => '/api/v1/analytics',
                'method' => 'GET',
                'status_code' => 200,
                'response_time_ms' => random_int(120, 400),
                'requested_at' => now()->subMinutes(30),
            ]);

            Tenant::limit(3)->get()->each(function (Tenant $tenant) {
                $key = ApiKey::create([
                    'tenant_id' => $tenant->id,
                    'name' => $tenant->name.' Key',
                    'key' => 'nsaas_'.Str::random(40),
                    'is_active' => true,
                    'expires_at' => now()->addMonths(3),
                ]);

                ApiUsageLog::create([
                    'api_key_id' => $key->id,
                    'tenant_id' => $tenant->id,
                    'endpoint' => '/api/v1/news/posts',
                    'method' => 'POST',
                    'status_code' => 201,
                    'response_time_ms' => random_int(150, 700),
                    'requested_at' => now()->subMinutes(random_int(60, 500)),
                ]);
            });
        }

        if (SupportTicket::count() === 0) {
            Tenant::with('users')->limit(4)->get()->each(function (Tenant $tenant, int $index) {
                $user = $tenant->users()->first();
                $ticket = SupportTicket::create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $user?->id,
                    'subject' => 'Support request #'.($index + 1),
                    'message' => 'Need help with publishing workflow and tenant settings.',
                    'priority' => ['low', 'medium', 'high'][($index % 3)],
                    'status' => ['open', 'in_progress', 'resolved'][($index % 3)],
                    'last_replied_at' => now()->subHours(random_int(1, 48)),
                ]);

                SupportTicketReply::create([
                    'support_ticket_id' => $ticket->id,
                    'user_id' => $user?->id,
                    'message' => 'Initial ticket raised from tenant portal.',
                    'is_internal' => false,
                ]);
            });
        }

        if (\App\Models\ActivityLog::count() < 15) {
            $tenant = Tenant::first();
            ActivityLogger::log('login_history', 'Login history synced', 'Recent login audit completed.', $tenant);
            ActivityLogger::log('tenant_changes', 'Tenant plan updated', 'Subscription plan changed to premium.', $tenant);
            ActivityLogger::log('news_actions', 'News post published', 'Editor published article with high traffic.', $tenant);
            ActivityLogger::log('payments', 'Gateway payout processed', 'Monthly payment settlement completed.', $tenant);
        }

        PlatformSetting::updateOrCreate(
            ['key' => 'maintenance.message'],
            ['group' => 'maintenance', 'value' => 'Platform is under scheduled maintenance.']
        );

        $extraDefaults = [
            'language.enabled' => ['language', 'te,en,hi'],
            'language.default' => ['language', 'en'],
            'cdn.provider' => ['cdn', 'cloudflare'],
            'cdn.cloudflare.zone_id' => ['cdn', 'sample-zone-id'],
            'cdn.cloudflare.api_token' => ['cdn', 'sample-api-token'],
            'cdn.cache_enabled' => ['cdn', '1'],
            'saas.company_name' => ['saas', 'News SaaS Platform'],
            'saas.company_email' => ['saas', 'support@newssaas.local'],
            'saas.brand_primary' => ['saas', '#2563eb'],
            'saas.brand_secondary' => ['saas', '#1e293b'],
            'saas.logo' => ['saas', ''],
            'saas.terms_url' => ['saas', 'https://example.com/terms'],
            'saas.privacy_url' => ['saas', 'https://example.com/privacy'],
        ];

        foreach ($extraDefaults as $key => [$group, $value]) {
            PlatformSetting::updateOrCreate(['key' => $key], ['group' => $group, 'value' => $value]);
        }

        if (AuditLog::count() === 0) {
            AuditLog::insert([
                [
                    'super_admin_id' => 1,
                    'entity_type' => 'tenant',
                    'entity_id' => 1,
                    'action' => 'tenant_updated',
                    'summary' => 'Tenant subscription upgraded.',
                    'changes' => json_encode(['plan' => 'premium']),
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Seeder',
                    'performed_at' => now()->subDays(1),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'super_admin_id' => 1,
                    'entity_type' => 'payment_gateway',
                    'entity_id' => 1,
                    'action' => 'payment_gateway_updated',
                    'summary' => 'Razorpay mode switched to test.',
                    'changes' => json_encode(['mode' => 'test']),
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Seeder',
                    'performed_at' => now()->subHours(10),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}
