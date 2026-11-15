<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class SuperAdminEnterpriseSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // 1. Roles
        $roles = [
            ['name' => 'Platform Owner', 'slug' => 'platform_owner', 'description' => 'Unrestricted super-privileges and system governance', 'is_system' => true],
            ['name' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Full administrative access across all modules', 'is_system' => true],
            ['name' => 'Operations Admin', 'slug' => 'operations_admin', 'description' => 'Manages tenants, domains, and newspaper operational pipelines', 'is_system' => false],
            ['name' => 'Finance Admin', 'slug' => 'finance_admin', 'description' => 'Manages subscriptions, packages, payments, and revenue reports', 'is_system' => false],
            ['name' => 'Support Admin', 'slug' => 'support_admin', 'description' => 'Handles tenant support tickets and notifications', 'is_system' => false],
            ['name' => 'Read-Only Admin', 'slug' => 'read_only_admin', 'description' => 'Audit and inspection access without mutation permissions', 'is_system' => false],
        ];

        $roleIds = [];
        foreach ($roles as $r) {
            $id = DB::table('super_admin_roles')->updateOrInsert(
                ['slug' => $r['slug']],
                array_merge($r, ['created_at' => $now, 'updated_at' => $now])
            );
            $roleIds[$r['slug']] = DB::table('super_admin_roles')->where('slug', $r['slug'])->value('id');
        }

        // 2. Permissions
        $permissions = [
            // Dashboard
            ['name' => 'View Dashboard', 'slug' => 'dashboard.view', 'module' => 'Dashboard'],
            // Tenants
            ['name' => 'View Tenants', 'slug' => 'tenants.view', 'module' => 'Tenants'],
            ['name' => 'Create Tenant', 'slug' => 'tenants.create', 'module' => 'Tenants'],
            ['name' => 'Edit Tenant', 'slug' => 'tenants.edit', 'module' => 'Tenants'],
            ['name' => 'Suspend Tenant', 'slug' => 'tenants.suspend', 'module' => 'Tenants'],
            ['name' => 'Delete Tenant', 'slug' => 'tenants.delete', 'module' => 'Tenants'],
            ['name' => 'Export Tenants', 'slug' => 'tenants.export', 'module' => 'Tenants'],
            // Domains & SSL
            ['name' => 'View Domains', 'slug' => 'domains.view', 'module' => 'Domains'],
            ['name' => 'Create Domain', 'slug' => 'domains.create', 'module' => 'Domains'],
            ['name' => 'Verify Domain', 'slug' => 'domains.verify', 'module' => 'Domains'],
            ['name' => 'Renew SSL', 'slug' => 'domains.renew_ssl', 'module' => 'Domains'],
            ['name' => 'Disconnect Domain', 'slug' => 'domains.disconnect', 'module' => 'Domains'],
            // Subscriptions
            ['name' => 'View Subscriptions', 'slug' => 'subscriptions.view', 'module' => 'Subscriptions'],
            ['name' => 'Manage Packages', 'slug' => 'subscriptions.packages', 'module' => 'Subscriptions'],
            ['name' => 'Upgrade/Downgrade', 'slug' => 'subscriptions.change', 'module' => 'Subscriptions'],
            // Payments
            ['name' => 'View Payments', 'slug' => 'payments.view', 'module' => 'Payments'],
            ['name' => 'Process Refund', 'slug' => 'payments.refund', 'module' => 'Payments'],
            ['name' => 'Reconcile Payments', 'slug' => 'payments.reconcile', 'module' => 'Payments'],
            // Branding
            ['name' => 'Manage Branding', 'slug' => 'branding.manage', 'module' => 'Branding'],
            // Newspaper Operations
            ['name' => 'View Operations', 'slug' => 'operations.view', 'module' => 'Operations'],
            ['name' => 'Retry Jobs', 'slug' => 'operations.retry_job', 'module' => 'Operations'],
            // Infrastructure
            ['name' => 'View Infrastructure', 'slug' => 'infrastructure.view', 'module' => 'Infrastructure'],
            ['name' => 'Manage Infrastructure', 'slug' => 'infrastructure.manage', 'module' => 'Infrastructure'],
            // Security
            ['name' => 'Manage Security', 'slug' => 'security.manage', 'module' => 'Security'],
            // Backups
            ['name' => 'Manage Backups', 'slug' => 'backups.manage', 'module' => 'Backups'],
            // Reports
            ['name' => 'Generate Reports', 'slug' => 'reports.generate', 'module' => 'Reports'],
            // Support
            ['name' => 'Manage Support', 'slug' => 'support.manage', 'module' => 'Support'],
            // Integrations & Settings
            ['name' => 'Manage Integrations', 'slug' => 'integrations.manage', 'module' => 'Integrations'],
            ['name' => 'Manage Settings', 'slug' => 'settings.manage', 'module' => 'Settings'],
        ];

        foreach ($permissions as $p) {
            DB::table('super_admin_permissions')->updateOrInsert(
                ['slug' => $p['slug']],
                array_merge($p, ['created_at' => $now, 'updated_at' => $now])
            );
            $pId = DB::table('super_admin_permissions')->where('slug', $p['slug'])->value('id');
            // Give all permissions to Platform Owner and Super Admin
            DB::table('super_admin_role_permissions')->updateOrInsert(
                ['role_id' => $roleIds['platform_owner'], 'permission_id' => $pId],
                []
            );
            DB::table('super_admin_role_permissions')->updateOrInsert(
                ['role_id' => $roleIds['super_admin'], 'permission_id' => $pId],
                []
            );
        }

        // 3. Super Admin User
        $adminId = DB::table('super_admins')->where('email', 'admin@dogyepaper.com')->value('id');
        if (!$adminId) {
            $adminId = DB::table('super_admins')->insertGetId([
                'name' => 'Chief Platform Architect',
                'email' => 'admin@dogyepaper.com',
                'phone' => '+91 98765 43210',
                'password' => Hash::make('SuperAdminPassword123!'),
                'role_id' => $roleIds['platform_owner'],
                'is_active' => true,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 4. Platform Branding
        DB::table('platform_branding')->updateOrInsert(
            ['id' => 1],
            [
                'platform_name' => 'DOGY ePAPER SaaS',
                'tag_line' => 'Enterprise White-Label Digital Newspaper Publishing Platform',
                'logo_url' => '/assets/branding/dogy-logo.svg',
                'favicon_url' => '/favicon.ico',
                'primary_color' => '#2563EB',
                'secondary_color' => '#0EA5E9',
                'header_config' => json_encode(['show_search' => true, 'show_date' => true, 'sticky' => true]),
                'footer_config' => json_encode(['copyright' => '© 2026 DOGY ePAPER SaaS. All rights reserved.']),
                'custom_css' => '/* Enterprise platform custom stylesheet */',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        // 5. Subscription Packages
        $packages = [
            [
                'name' => 'Starter Daily',
                'slug' => 'starter-daily',
                'description' => 'Essential publishing for local & regional single-edition publishers.',
                'billing_cycle' => 'monthly',
                'price' => 4999.00,
                'currency' => 'INR',
                'trial_days' => 14,
                'is_active' => true,
                'is_popular' => false,
                'is_custom' => false,
                'version' => 1,
                'entitlements' => [
                    'max_domains' => 1,
                    'storage_limit_gb' => 10,
                    'pdf_upload_limit_mb' => 100,
                    'monthly_edition_limit' => 31,
                    'admin_user_limit' => 3,
                    'custom_branding_enabled' => true,
                    'priority_support_enabled' => false,
                    'analytics_enabled' => true,
                ]
            ],
            [
                'name' => 'Professional Publisher',
                'slug' => 'professional-publisher',
                'description' => 'High-throughput publishing for multi-district editions with custom domains.',
                'billing_cycle' => 'monthly',
                'price' => 14999.00,
                'currency' => 'INR',
                'trial_days' => 14,
                'is_active' => true,
                'is_popular' => true,
                'is_custom' => false,
                'version' => 1,
                'entitlements' => [
                    'max_domains' => 5,
                    'storage_limit_gb' => 50,
                    'pdf_upload_limit_mb' => 250,
                    'monthly_edition_limit' => 100,
                    'admin_user_limit' => 10,
                    'custom_branding_enabled' => true,
                    'priority_support_enabled' => true,
                    'analytics_enabled' => true,
                ]
            ],
            [
                'name' => 'Enterprise Media Group',
                'slug' => 'enterprise-media-group',
                'description' => 'Statewide newspaper conglomerates with multi-edition archives and custom SLA.',
                'billing_cycle' => 'yearly',
                'price' => 149999.00,
                'currency' => 'INR',
                'trial_days' => 30,
                'is_active' => true,
                'is_popular' => false,
                'is_custom' => false,
                'version' => 1,
                'entitlements' => [
                    'max_domains' => 20,
                    'storage_limit_gb' => 500,
                    'pdf_upload_limit_mb' => 500,
                    'monthly_edition_limit' => 600,
                    'admin_user_limit' => 50,
                    'custom_branding_enabled' => true,
                    'priority_support_enabled' => true,
                    'analytics_enabled' => true,
                ]
            ]
        ];

        $packageIds = [];
        foreach ($packages as $pkg) {
            $entitlements = $pkg['entitlements'];
            unset($pkg['entitlements']);
            
            DB::table('subscription_packages')->updateOrInsert(
                ['slug' => $pkg['slug']],
                array_merge($pkg, ['created_at' => $now, 'updated_at' => $now])
            );
            $pId = DB::table('subscription_packages')->where('slug', $pkg['slug'])->value('id');
            $packageIds[$pkg['slug']] = $pId;

            DB::table('package_entitlements')->updateOrInsert(
                ['package_id' => $pId],
                array_merge($entitlements, ['package_id' => $pId, 'created_at' => $now, 'updated_at' => $now])
            );
        }

        // 6. Sample Newspaper Tenants
        $sampleTenants = [
            [
                'name' => 'Telangana Samacharam',
                'slug' => 'telangana-samacharam',
                'company_name' => 'Samacharam Media Pvt Ltd',
                'contact_person' => 'K. R. Rao',
                'email' => 'publisher@samacharam.com',
                'phone' => '+91 98480 12345',
                'domain' => 'epaper.samacharam.com',
                'state' => 'Telangana',
                'district' => 'Hyderabad',
                'mandal' => 'Khairatabad',
                'subscription' => 'Professional Publisher',
                'package_id' => $packageIds['professional-publisher'],
                'subscription_amount' => 14999.00,
                'billing_cycle' => 'monthly',
                'status' => 'active',
                'storage_limit_mb' => 51200,
                'storage_used_mb' => 14250.50,
                'trial_ends_at' => null,
                'subscription_ends_at' => $now->copy()->addMonths(6),
            ],
            [
                'name' => 'Andhra Prabha Digital',
                'slug' => 'andhra-prabha-digital',
                'company_name' => 'Prabha Publications Ltd',
                'contact_person' => 'S. V. Murthy',
                'email' => 'editions@andhraprabha.com',
                'phone' => '+91 98490 54321',
                'domain' => 'epaper.andhraprabha.com',
                'state' => 'Andhra Pradesh',
                'district' => 'Vijayawada',
                'mandal' => 'Governorpet',
                'subscription' => 'Enterprise Media Group',
                'package_id' => $packageIds['enterprise-media-group'],
                'subscription_amount' => 149999.00,
                'billing_cycle' => 'yearly',
                'status' => 'active',
                'storage_limit_mb' => 512000,
                'storage_used_mb' => 98420.00,
                'trial_ends_at' => null,
                'subscription_ends_at' => $now->copy()->addMonths(11),
            ],
            [
                'name' => 'Deccan Herald Daily',
                'slug' => 'deccan-herald-daily',
                'company_name' => 'Deccan Newsline Network',
                'contact_person' => 'P. V. Reddy',
                'email' => 'contact@deccandaily.in',
                'phone' => '+91 97000 98765',
                'domain' => 'read.deccandaily.in',
                'state' => 'Telangana',
                'district' => 'Warangal',
                'mandal' => 'Hanamkonda',
                'subscription' => 'Starter Daily',
                'package_id' => $packageIds['starter-daily'],
                'subscription_amount' => 4999.00,
                'billing_cycle' => 'monthly',
                'status' => 'trial',
                'storage_limit_mb' => 10240,
                'storage_used_mb' => 1840.00,
                'trial_ends_at' => $now->copy()->addDays(8),
                'subscription_ends_at' => null,
            ],
            [
                'name' => 'Godavari Voice News',
                'slug' => 'godavari-voice',
                'company_name' => 'Godavari Media Trust',
                'contact_person' => 'N. Srinivas',
                'email' => 'admin@godavarivoice.org',
                'phone' => '+91 94401 22334',
                'domain' => 'epaper.godavarivoice.org',
                'state' => 'Andhra Pradesh',
                'district' => 'East Godavari',
                'mandal' => 'Rajahmundry',
                'subscription' => 'Starter Daily',
                'package_id' => $packageIds['starter-daily'],
                'subscription_amount' => 4999.00,
                'billing_cycle' => 'monthly',
                'status' => 'suspended',
                'storage_limit_mb' => 10240,
                'storage_used_mb' => 8400.00,
                'trial_ends_at' => null,
                'subscription_ends_at' => $now->copy()->subDays(10),
                'suspended_at' => $now->copy()->subDays(2),
            ],
        ];

        foreach ($sampleTenants as $tData) {
            $existing = DB::table('tenants')->where('slug', $tData['slug'])->first();
            if ($existing) {
                $tenantId = $existing->id;
                DB::table('tenants')->where('id', $tenantId)->update(array_merge($tData, ['updated_at' => $now]));
            } else {
                $tenantId = DB::table('tenants')->insertGetId(array_merge($tData, ['created_at' => $now, 'updated_at' => $now]));
            }

            // Tenant Domain Record
            DB::table('tenant_domains')->updateOrInsert(
                ['tenant_id' => $tenantId, 'host' => $tData['domain']],
                [
                    'type' => 'custom',
                    'is_primary' => true,
                    'verification_status' => ($tData['status'] === 'active') ? 'verified' : 'pending',
                    'verification_token' => 'dogy-verify-' . substr(md5($tData['slug']), 0, 16),
                    'txt_record_host' => '_dogy-challenge.' . $tData['domain'],
                    'txt_record_value' => 'dogy-auth=' . hash('sha256', $tData['slug']),
                    'cname_host' => $tData['domain'],
                    'cname_target' => 'cname.dogyepaper.com',
                    'ssl_status' => ($tData['status'] === 'active') ? 'active' : 'pending',
                    'ssl_auto' => true,
                    'acm_certificate_arn' => ($tData['status'] === 'active') ? 'arn:aws:acm:us-east-1:123456789012:certificate/' . substr(md5($tData['domain']), 0, 8) : null,
                    'cloudfront_distribution_id' => ($tData['status'] === 'active') ? 'E' . strtoupper(substr(md5($tData['domain']), 0, 12)) : null,
                    'ssl_issued_at' => ($tData['status'] === 'active') ? $now->copy()->subMonths(1) : null,
                    'ssl_expires_at' => ($tData['status'] === 'active') ? $now->copy()->addMonths(11) : null,
                    'health_status' => ($tData['status'] === 'active') ? 'healthy' : 'warning',
                    'last_health_check_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            // Tenant Branding Record
            DB::table('tenant_branding')->updateOrInsert(
                ['tenant_id' => $tenantId],
                [
                    'newspaper_name' => $tData['name'],
                    'primary_color' => '#1E40AF',
                    'secondary_color' => '#0284C7',
                    'typography' => 'Inter, sans-serif',
                    'is_published' => true,
                    'version' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            // Tenant Subscription Record
            DB::table('tenant_subscriptions')->updateOrInsert(
                ['tenant_id' => $tenantId],
                [
                    'package_id' => $tData['package_id'],
                    'status' => $tData['status'] === 'suspended' ? 'suspended' : ($tData['status'] === 'trial' ? 'trial' : 'active'),
                    'billing_cycle' => $tData['billing_cycle'],
                    'amount' => $tData['subscription_amount'],
                    'currency' => 'INR',
                    'current_period_start' => $now->copy()->subDays(15),
                    'current_period_end' => $tData['subscription_ends_at'],
                    'trial_ends_at' => $tData['trial_ends_at'],
                    'auto_renew' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            // Payment Transaction for active tenants
            if ($tData['status'] === 'active') {
                $invNumber = 'INV-' . strtoupper(substr($tData['slug'], 0, 4)) . '-' . rand(1000, 9999);
                $invId = DB::table('subscription_invoices')->insertGetId([
                    'tenant_id' => $tenantId,
                    'invoice_number' => $invNumber,
                    'amount' => $tData['subscription_amount'],
                    'tax_amount' => $tData['subscription_amount'] * 0.18,
                    'currency' => 'INR',
                    'status' => 'paid',
                    'due_date' => $now->copy()->subDays(5),
                    'paid_at' => $now->copy()->subDays(4),
                    'created_at' => $now->copy()->subDays(5),
                    'updated_at' => $now->copy()->subDays(4),
                ]);

                DB::table('payments')->insert([
                    'tenant_id' => $tenantId,
                    'invoice_id' => $invId,
                    'transaction_reference' => 'PAY-' . strtoupper(bin2hex(random_bytes(6))),
                    'gateway' => 'razorpay',
                    'amount' => $tData['subscription_amount'] * 1.18,
                    'tax' => $tData['subscription_amount'] * 0.18,
                    'fee' => 50.00,
                    'currency' => 'INR',
                    'status' => 'success',
                    'payment_method' => 'UPI / NetBanking',
                    'created_at' => $now->copy()->subDays(4),
                    'updated_at' => $now->copy()->subDays(4),
                ]);
            }
        }

        // 7. AWS Infrastructure Services
        $infraServices = [
            ['service_name' => 'ecs_api', 'service_type' => 'ECS Fargate API Fleet', 'aws_region' => 'ap-south-1', 'resource_arn' => 'arn:aws:ecs:ap-south-1:123456789012:service/dogy-api', 'status' => 'healthy', 'cpu_percent' => 28.5, 'memory_percent' => 45.2, 'latency_ms' => 42],
            ['service_name' => 'ecs_worker', 'service_type' => 'ECS Fargate PDF Conversion Workers', 'aws_region' => 'ap-south-1', 'resource_arn' => 'arn:aws:ecs:ap-south-1:123456789012:service/dogy-pdf-workers', 'status' => 'healthy', 'cpu_percent' => 54.0, 'memory_percent' => 62.8, 'latency_ms' => 120],
            ['service_name' => 'rds_mysql', 'service_type' => 'Amazon RDS MySQL Multi-AZ Cluster', 'aws_region' => 'ap-south-1', 'resource_arn' => 'arn:aws:rds:ap-south-1:123456789012:db:dogy-primary-db', 'status' => 'healthy', 'cpu_percent' => 32.1, 'memory_percent' => 58.4, 'latency_ms' => 4],
            ['service_name' => 'elasticache_redis', 'service_type' => 'Amazon ElastiCache Redis Cluster', 'aws_region' => 'ap-south-1', 'resource_arn' => 'arn:aws:elasticache:ap-south-1:123456789012:cluster/dogy-redis', 'status' => 'healthy', 'cpu_percent' => 14.8, 'memory_percent' => 31.0, 'latency_ms' => 1],
            ['service_name' => 's3_storage', 'service_type' => 'Amazon S3 Standard Bucket (PDF & Pages)', 'aws_region' => 'ap-south-1', 'resource_arn' => 'arn:aws:s3:::dogy-epaper-storage-production', 'status' => 'healthy', 'cpu_percent' => 0.0, 'memory_percent' => 0.0, 'latency_ms' => 24],
            ['service_name' => 'cloudfront_cdn', 'service_type' => 'CloudFront CDN Edge Fleet', 'aws_region' => 'global', 'resource_arn' => 'arn:aws:cloudfront::123456789012:distribution/E2Z8Y8TESTCDN', 'status' => 'healthy', 'cpu_percent' => 0.0, 'memory_percent' => 0.0, 'latency_ms' => 18],
            ['service_name' => 'sqs_queue', 'service_type' => 'Amazon SQS High-Throughput PDF Queue', 'aws_region' => 'ap-south-1', 'resource_arn' => 'arn:aws:sqs:ap-south-1:123456789012:dogy-pdf-conversion-jobs', 'status' => 'healthy', 'cpu_percent' => 0.0, 'memory_percent' => 0.0, 'latency_ms' => 12],
            ['service_name' => 'waf', 'service_type' => 'AWS WAF Enterprise Firewall & Rate Limiting', 'aws_region' => 'global', 'resource_arn' => 'arn:aws:wafv2:global:123456789012:global/webacl/dogy-waf', 'status' => 'healthy', 'cpu_percent' => 0.0, 'memory_percent' => 0.0, 'latency_ms' => 5],
        ];

        foreach ($infraServices as $srv) {
            DB::table('infrastructure_services')->updateOrInsert(
                ['service_name' => $srv['service_name']],
                array_merge($srv, ['last_checked_at' => $now, 'created_at' => $now, 'updated_at' => $now])
            );
        }

        // 8. Newspaper Operations Jobs
        $firstTenant = DB::table('tenants')->first();
        if ($firstTenant) {
            $editionId = DB::table('newspaper_editions')->insertGetId([
                'tenant_id' => $firstTenant->id,
                'edition_code' => 'ED-' . date('Ymd') . '-01',
                'edition_name' => 'Main City Morning Edition - ' . date('d M Y'),
                'publish_date' => Carbon::today(),
                'total_pages' => 16,
                'file_size_mb' => 48.5,
                'pdf_s3_path' => 'tenants/' . $firstTenant->slug . '/editions/2026/11/14/main.pdf',
                'status' => 'completed',
                'created_at' => $now->copy()->subHours(6),
                'updated_at' => $now->copy()->subHours(6),
            ]);

            DB::table('pdf_processing_jobs')->insert([
                'edition_id' => $editionId,
                'tenant_id' => $firstTenant->id,
                'job_uuid' => 'job-' . bin2hex(random_bytes(8)),
                'status' => 'completed',
                'current_page' => 16,
                'total_pages' => 16,
                'worker_id' => 'ecs-worker-node-14a',
                'attempts' => 1,
                'started_at' => $now->copy()->subHours(6),
                'completed_at' => $now->copy()->subHours(6)->addMinutes(3),
                'created_at' => $now->copy()->subHours(6),
                'updated_at' => $now->copy()->subHours(6)->addMinutes(3),
            ]);
        }

        // 9. Backup Records
        DB::table('backup_records')->updateOrInsert(
            ['backup_type' => 'rds_snapshot'],
            [
                'type' => 'database',
                'file_path' => 's3://dogy-epaper-backups/snapshot-rds-dogy-prod-20261114.snap',
                'file_size' => 10737418240,
                'status' => 'ready',
                'backup_taken_at' => $now->copy()->subHours(8),
                'notes' => 'RDS Automated Daily Snapshot - ' . date('Y-m-d'),
                'aws_backup_arn' => 'arn:aws:backup:ap-south-1:123456789012:recovery-point:rds-snap-prod-20261114',
                'encryption_status' => 'AES-256 (AWS KMS)',
                'retention_days' => 30,
                'rpo_minutes' => 5,
                'rto_minutes' => 30,
                'completed_at' => $now->copy()->subHours(8),
                'expires_at' => $now->copy()->addDays(30),
                'created_at' => $now->copy()->subHours(8),
                'updated_at' => $now->copy()->subHours(8),
            ]
        );

        // 10. Audit Log Initial Entry
        DB::table('audit_logs')->insert([
            'super_admin_id' => $adminId,
            'entity_type' => 'Platform',
            'entity_id' => 1,
            'action' => 'PLATFORM_INITIALIZATION',
            'summary' => 'Super Admin Enterprise Control Center initialized with RBAC and standard tenancy blueprints.',
            'changes' => json_encode(['status' => 'initialized', 'version' => '2.0-enterprise']),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'DogyEnterpriseAgent/1.0',
            'performed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
