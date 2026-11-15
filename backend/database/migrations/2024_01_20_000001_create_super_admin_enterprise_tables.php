<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Super Admin Roles & Permissions
        if (!Schema::hasTable('super_admin_roles')) {
            Schema::create('super_admin_roles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_system')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('super_admin_permissions')) {
            Schema::create('super_admin_permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('module');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('super_admin_role_permissions')) {
            Schema::create('super_admin_role_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('role_id')->constrained('super_admin_roles')->cascadeOnDelete();
                $table->foreignId('permission_id')->constrained('super_admin_permissions')->cascadeOnDelete();
                $table->unique(['role_id', 'permission_id']);
            });
        }

        // Add role_id and security fields to super_admins
        Schema::table('super_admins', function (Blueprint $table) {
            if (!Schema::hasColumn('super_admins', 'role_id')) {
                $table->foreignId('role_id')->nullable()->after('id')->constrained('super_admin_roles')->nullOnDelete();
            }
            if (!Schema::hasColumn('super_admins', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            if (!Schema::hasColumn('super_admins', 'avatar')) {
                $table->string('avatar')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('super_admins', 'mfa_enabled')) {
                $table->boolean('mfa_enabled')->default(false)->after('password');
            }
            if (!Schema::hasColumn('super_admins', 'mfa_secret')) {
                $table->string('mfa_secret')->nullable()->after('mfa_enabled');
            }
            if (!Schema::hasColumn('super_admins', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('super_admins', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            }
            if (!Schema::hasColumn('super_admins', 'status')) {
                $table->string('status', 20)->default('active')->after('last_login_ip');
            }
        });

        if (!Schema::hasTable('super_admin_sessions')) {
            Schema::create('super_admin_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('super_admin_id')->constrained('super_admins')->cascadeOnDelete();
                $table->string('ip_address', 45);
                $table->text('user_agent')->nullable();
                $table->string('token_id')->nullable()->index();
                $table->boolean('is_current')->default(false);
                $table->timestamp('last_activity');
                $table->timestamps();
            });
        }

        // 2. Extend Tenants table
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'company_name')) {
                $table->string('company_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('tenants', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('company_name');
            }
            if (!Schema::hasColumn('tenants', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            if (!Schema::hasColumn('tenants', 'address')) {
                $table->text('address')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('tenants', 'state')) {
                $table->string('state')->nullable()->after('address');
            }
            if (!Schema::hasColumn('tenants', 'district')) {
                $table->string('district')->nullable()->after('state');
            }
            if (!Schema::hasColumn('tenants', 'mandal')) {
                $table->string('mandal')->nullable()->after('district');
            }
            if (!Schema::hasColumn('tenants', 'package_id')) {
                $table->unsignedBigInteger('package_id')->nullable()->after('subscription');
            }
            if (!Schema::hasColumn('tenants', 'billing_cycle')) {
                $table->string('billing_cycle', 20)->default('monthly')->after('package_id');
            }
            if (!Schema::hasColumn('tenants', 'storage_limit_mb')) {
                $table->unsignedBigInteger('storage_limit_mb')->default(5120)->after('subscription_amount');
            }
            if (!Schema::hasColumn('tenants', 'storage_used_mb')) {
                $table->decimal('storage_used_mb', 10, 2)->default(0)->after('storage_limit_mb');
            }
            if (!Schema::hasColumn('tenants', 'default_language')) {
                $table->string('default_language', 10)->default('en')->after('storage_used_mb');
            }
            if (!Schema::hasColumn('tenants', 'timezone')) {
                $table->string('timezone', 50)->default('Asia/Kolkata')->after('default_language');
            }
            if (!Schema::hasColumn('tenants', 'database_name')) {
                $table->string('database_name')->nullable()->after('timezone');
            }
            if (!Schema::hasColumn('tenants', 'storage_bucket')) {
                $table->string('storage_bucket')->nullable()->after('database_name');
            }
            if (!Schema::hasColumn('tenants', 'storage_prefix')) {
                $table->string('storage_prefix')->nullable()->after('storage_bucket');
            }
        });

        if (!Schema::hasTable('tenant_contacts')) {
            Schema::create('tenant_contacts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('name');
                $table->string('designation')->nullable();
                $table->string('email');
                $table->string('phone')->nullable();
                $table->boolean('is_primary')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tenant_configurations')) {
            Schema::create('tenant_configurations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('key');
                $table->text('value')->nullable();
                $table->string('type', 30)->default('string');
                $table->boolean('is_encrypted')->default(false);
                $table->timestamps();
                $table->unique(['tenant_id', 'key']);
            });
        }

        if (!Schema::hasTable('tenant_admin_invitations')) {
            Schema::create('tenant_admin_invitations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('name');
                $table->string('email');
                $table->string('phone')->nullable();
                $table->string('token', 64)->unique();
                $table->string('status', 20)->default('pending'); // pending, accepted, expired, revoked
                $table->timestamp('expires_at');
                $table->timestamp('accepted_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tenant_status_history')) {
            Schema::create('tenant_status_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('previous_status', 30)->nullable();
                $table->string('new_status', 30);
                $table->text('reason')->nullable();
                $table->foreignId('changed_by_admin_id')->nullable()->constrained('super_admins')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tenant_provisioning_logs')) {
            Schema::create('tenant_provisioning_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('step');
                $table->string('status', 20); // pending, in_progress, completed, failed
                $table->text('message')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }

        // 3. Extend Tenant Domains
        Schema::table('tenant_domains', function (Blueprint $table) {
            if (!Schema::hasColumn('tenant_domains', 'verification_status')) {
                $table->string('verification_status', 30)->default('pending')->after('is_primary');
            }
            if (!Schema::hasColumn('tenant_domains', 'verification_token')) {
                $table->string('verification_token')->nullable()->after('verification_status');
            }
            if (!Schema::hasColumn('tenant_domains', 'txt_record_host')) {
                $table->string('txt_record_host')->nullable()->after('verification_token');
            }
            if (!Schema::hasColumn('tenant_domains', 'txt_record_value')) {
                $table->string('txt_record_value')->nullable()->after('txt_record_host');
            }
            if (!Schema::hasColumn('tenant_domains', 'cname_host')) {
                $table->string('cname_host')->nullable()->after('txt_record_value');
            }
            if (!Schema::hasColumn('tenant_domains', 'cname_target')) {
                $table->string('cname_target')->nullable()->after('cname_host');
            }
            if (!Schema::hasColumn('tenant_domains', 'acm_certificate_arn')) {
                $table->string('acm_certificate_arn')->nullable()->after('ssl_status');
            }
            if (!Schema::hasColumn('tenant_domains', 'cloudfront_distribution_id')) {
                $table->string('cloudfront_distribution_id')->nullable()->after('acm_certificate_arn');
            }
            if (!Schema::hasColumn('tenant_domains', 'dns_checked_at')) {
                $table->timestamp('dns_checked_at')->nullable()->after('ssl_expires_at');
            }
            if (!Schema::hasColumn('tenant_domains', 'dns_error')) {
                $table->text('dns_error')->nullable()->after('dns_checked_at');
            }
            if (!Schema::hasColumn('tenant_domains', 'health_status')) {
                $table->string('health_status', 20)->default('unknown')->after('dns_error');
            }
            if (!Schema::hasColumn('tenant_domains', 'last_health_check_at')) {
                $table->timestamp('last_health_check_at')->nullable()->after('health_status');
            }
        });

        if (!Schema::hasTable('domain_dns_records')) {
            Schema::create('domain_dns_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('domain_id')->constrained('tenant_domains')->cascadeOnDelete();
                $table->string('record_type', 10); // A, CNAME, TXT, ALIAS
                $table->string('host');
                $table->string('expected_value');
                $table->string('current_value')->nullable();
                $table->string('status', 20)->default('pending'); // pending, matched, mismatched, error
                $table->timestamp('last_verified_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('domain_ssl_certificates')) {
            Schema::create('domain_ssl_certificates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('domain_id')->constrained('tenant_domains')->cascadeOnDelete();
                $table->string('certificate_arn')->nullable();
                $table->string('issuer')->default('Amazon Trust Services');
                $table->string('domain_name');
                $table->json('sans')->nullable();
                $table->string('status', 20)->default('pending');
                $table->timestamp('valid_from')->nullable();
                $table->timestamp('valid_to')->nullable();
                $table->boolean('auto_renew')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('domain_activity_logs')) {
            Schema::create('domain_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('domain_id')->constrained('tenant_domains')->cascadeOnDelete();
                $table->string('action');
                $table->string('status', 20);
                $table->text('details')->nullable();
                $table->foreignId('actor_id')->nullable()->constrained('super_admins')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 4. Subscription & Packages
        if (!Schema::hasTable('subscription_packages')) {
            Schema::create('subscription_packages', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('billing_cycle', 20)->default('monthly'); // monthly, quarterly, half_yearly, yearly, custom
                $table->decimal('price', 10, 2)->default(0);
                $table->string('currency', 10)->default('INR');
                $table->integer('trial_days')->default(14);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_popular')->default(false);
                $table->boolean('is_custom')->default(false);
                $table->integer('version')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('package_entitlements')) {
            Schema::create('package_entitlements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('package_id')->constrained('subscription_packages')->cascadeOnDelete();
                $table->integer('max_domains')->default(1);
                $table->unsignedBigInteger('storage_limit_gb')->default(10);
                $table->integer('pdf_upload_limit_mb')->default(100);
                $table->integer('monthly_edition_limit')->default(31);
                $table->integer('admin_user_limit')->default(3);
                $table->boolean('custom_branding_enabled')->default(true);
                $table->boolean('priority_support_enabled')->default(false);
                $table->boolean('analytics_enabled')->default(true);
                $table->json('custom_entitlements')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tenant_subscriptions')) {
            Schema::create('tenant_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('package_id')->constrained('subscription_packages');
                $table->string('status', 30)->default('active'); // active, trial, pending, expired, suspended, cancelled
                $table->string('billing_cycle', 20)->default('monthly');
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('currency', 10)->default('INR');
                $table->timestamp('current_period_start')->nullable();
                $table->timestamp('current_period_end')->nullable();
                $table->timestamp('trial_ends_at')->nullable();
                $table->timestamp('grace_period_ends_at')->nullable();
                $table->boolean('auto_renew')->default(true);
                $table->json('custom_limits')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('subscription_history')) {
            Schema::create('subscription_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('package_id')->constrained('subscription_packages');
                $table->string('event', 30); // created, upgraded, downgraded, renewed, expired, cancelled
                $table->unsignedBigInteger('old_package_id')->nullable();
                $table->unsignedBigInteger('new_package_id')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('actor_id')->nullable()->constrained('super_admins')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('subscription_invoices')) {
            Schema::create('subscription_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('subscription_id')->nullable()->constrained('tenant_subscriptions')->nullOnDelete();
                $table->string('invoice_number')->unique();
                $table->decimal('amount', 10, 2);
                $table->decimal('tax_amount', 10, 2)->default(0);
                $table->string('currency', 10)->default('INR');
                $table->string('status', 20)->default('pending'); // draft, pending, paid, void, refunded
                $table->timestamp('due_date')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->string('invoice_pdf_url')->nullable();
                $table->timestamps();
            });
        }

        // 5. Payments & Financial Operations
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('invoice_id')->nullable()->constrained('subscription_invoices')->nullOnDelete();
                $table->string('transaction_reference')->unique();
                $table->string('gateway', 30); // razorpay, stripe, paypal, manual
                $table->decimal('amount', 10, 2);
                $table->decimal('tax', 10, 2)->default(0);
                $table->decimal('fee', 10, 2)->default(0);
                $table->string('currency', 10)->default('INR');
                $table->string('status', 20)->default('pending'); // pending, success, failed, refunded
                $table->string('payment_method')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payment_transactions')) {
            Schema::create('payment_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
                $table->string('gateway_transaction_id')->nullable()->index();
                $table->string('gateway_order_id')->nullable()->index();
                $table->json('payload')->nullable();
                $table->json('response')->nullable();
                $table->string('status', 20);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payment_webhook_events')) {
            Schema::create('payment_webhook_events', function (Blueprint $table) {
                $table->id();
                $table->string('gateway', 30);
                $table->string('event_type');
                $table->string('event_id')->unique();
                $table->json('payload');
                $table->boolean('is_processed')->default(false);
                $table->timestamp('processed_at')->nullable();
                $table->boolean('signature_verified')->default(false);
                $table->text('error')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('refunds')) {
            Schema::create('refunds', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('refund_reference')->unique();
                $table->decimal('amount', 10, 2);
                $table->text('reason')->nullable();
                $table->string('status', 20)->default('pending'); // pending, completed, failed
                $table->foreignId('processed_by_admin_id')->nullable()->constrained('super_admins')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payment_reconciliation_logs')) {
            Schema::create('payment_reconciliation_logs', function (Blueprint $table) {
                $table->id();
                $table->date('period_start');
                $table->date('period_end');
                $table->string('gateway', 30);
                $table->decimal('total_expected', 12, 2);
                $table->decimal('total_settled', 12, 2);
                $table->decimal('discrepancy_amount', 12, 2)->default(0);
                $table->string('status', 20)->default('matched'); // matched, discrepancy, investigated
                $table->text('notes')->nullable();
                $table->foreignId('reconciled_by_admin_id')->nullable()->constrained('super_admins')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 6. White Label & Branding Engine
        if (!Schema::hasTable('platform_branding')) {
            Schema::create('platform_branding', function (Blueprint $table) {
                $table->id();
                $table->string('platform_name')->default('DOGY ePAPER SaaS');
                $table->string('tag_line')->nullable();
                $table->string('logo_url')->nullable();
                $table->string('favicon_url')->nullable();
                $table->string('primary_color', 10)->default('#2563EB');
                $table->string('secondary_color', 10)->default('#0EA5E9');
                $table->json('header_config')->nullable();
                $table->json('footer_config')->nullable();
                $table->text('custom_css')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tenant_branding')) {
            Schema::create('tenant_branding', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('newspaper_name');
                $table->string('logo_url')->nullable();
                $table->string('favicon_url')->nullable();
                $table->string('primary_color', 10)->default('#2563EB');
                $table->string('secondary_color', 10)->default('#0EA5E9');
                $table->string('typography', 50)->default('Inter, sans-serif');
                $table->json('nav_links')->nullable();
                $table->json('header_config')->nullable();
                $table->json('footer_config')->nullable();
                $table->json('social_links')->nullable();
                $table->text('custom_css')->nullable();
                $table->boolean('is_published')->default(true);
                $table->integer('version')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('branding_versions')) {
            Schema::create('branding_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->integer('version_number');
                $table->json('configuration');
                $table->timestamp('published_at');
                $table->foreignId('published_by_admin_id')->nullable()->constrained('super_admins')->nullOnDelete();
                $table->text('change_summary')->nullable();
                $table->timestamps();
            });
        }

        // 7. Newspaper Operations Monitoring
        if (!Schema::hasTable('newspaper_editions')) {
            Schema::create('newspaper_editions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('edition_code')->index();
                $table->string('edition_name');
                $table->date('publish_date');
                $table->integer('total_pages')->default(0);
                $table->decimal('file_size_mb', 8, 2)->default(0);
                $table->string('pdf_s3_path')->nullable();
                $table->string('status', 30)->default('pending'); // pending, processing, completed, failed
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pdf_processing_jobs')) {
            Schema::create('pdf_processing_jobs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('edition_id')->constrained('newspaper_editions')->cascadeOnDelete();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('job_uuid')->unique();
                $table->string('status', 30)->default('queued'); // queued, processing, completed, failed, retrying, dead_letter
                $table->integer('current_page')->default(0);
                $table->integer('total_pages')->default(0);
                $table->string('worker_id')->nullable();
                $table->integer('attempts')->default(0);
                $table->text('error_message')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pdf_processing_logs')) {
            Schema::create('pdf_processing_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('job_id')->constrained('pdf_processing_jobs')->cascadeOnDelete();
                $table->string('stage');
                $table->text('message');
                $table->string('level', 10)->default('info'); // info, warning, error
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('storage_usage_records')) {
            Schema::create('storage_usage_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->date('record_date');
                $table->decimal('pdf_storage_mb', 10, 2)->default(0);
                $table->decimal('page_images_storage_mb', 10, 2)->default(0);
                $table->decimal('thumbnails_storage_mb', 10, 2)->default(0);
                $table->decimal('total_storage_mb', 10, 2)->default(0);
                $table->integer('file_count')->default(0);
                $table->timestamps();
                $table->unique(['tenant_id', 'record_date']);
            });
        }

        // 8. Infrastructure & Cloud Monitoring
        if (!Schema::hasTable('infrastructure_services')) {
            Schema::create('infrastructure_services', function (Blueprint $table) {
                $table->id();
                $table->string('service_name'); // ecs_api, ecs_worker, rds_mysql, elasticache_redis, s3_storage, cloudfront_cdn, sqs_queue, acm_ssl, waf, cloudwatch
                $table->string('service_type');
                $table->string('aws_region')->default('ap-south-1');
                $table->string('resource_arn')->nullable();
                $table->string('status', 20)->default('healthy'); // healthy, degraded, critical, maintenance
                $table->decimal('cpu_percent', 5, 2)->default(0);
                $table->decimal('memory_percent', 5, 2)->default(0);
                $table->integer('latency_ms')->default(0);
                $table->timestamp('last_checked_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('infrastructure_health_logs')) {
            Schema::create('infrastructure_health_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_id')->constrained('infrastructure_services')->cascadeOnDelete();
                $table->string('metric_name');
                $table->decimal('metric_value', 12, 2);
                $table->string('unit', 20)->default('count');
                $table->timestamp('recorded_at');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('infrastructure_alerts')) {
            Schema::create('infrastructure_alerts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_id')->nullable()->constrained('infrastructure_services')->nullOnDelete();
                $table->string('severity', 20)->default('medium'); // low, medium, high, critical
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('status', 20)->default('active'); // active, acknowledged, resolved
                $table->timestamp('triggered_at');
                $table->timestamp('resolved_at')->nullable();
                $table->foreignId('resolved_by_admin_id')->nullable()->constrained('super_admins')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('deployment_logs')) {
            Schema::create('deployment_logs', function (Blueprint $table) {
                $table->id();
                $table->string('service_name');
                $table->string('version_tag');
                $table->string('commit_hash')->nullable();
                $table->string('status', 20)->default('success'); // in_progress, success, failed, rolled_back
                $table->string('triggered_by')->default('system');
                $table->text('summary')->nullable();
                $table->timestamp('started_at');
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();
            });
        }

        // 9. Backup & Disaster Recovery
        Schema::table('backup_records', function (Blueprint $table) {
            if (!Schema::hasColumn('backup_records', 'backup_type')) {
                $table->string('backup_type', 30)->default('rds_snapshot')->after('id');
            }
            if (!Schema::hasColumn('backup_records', 'aws_backup_arn')) {
                $table->string('aws_backup_arn')->nullable()->after('backup_type');
            }
            if (!Schema::hasColumn('backup_records', 'encryption_status')) {
                $table->string('encryption_status', 20)->default('AES-256')->after('status');
            }
            if (!Schema::hasColumn('backup_records', 'retention_days')) {
                $table->integer('retention_days')->default(30)->after('encryption_status');
            }
            if (!Schema::hasColumn('backup_records', 'rpo_minutes')) {
                $table->integer('rpo_minutes')->default(5)->after('retention_days');
            }
            if (!Schema::hasColumn('backup_records', 'rto_minutes')) {
                $table->integer('rto_minutes')->default(60)->after('rpo_minutes');
            }
            if (!Schema::hasColumn('backup_records', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('rto_minutes');
            }
            if (!Schema::hasColumn('backup_records', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('completed_at');
            }
        });

        if (!Schema::hasTable('backup_verification_logs')) {
            Schema::create('backup_verification_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('backup_id')->constrained('backup_records')->cascadeOnDelete();
                $table->string('verification_type', 30); // checksum, test_restore, integrity_scan
                $table->string('result', 20); // passed, failed
                $table->text('details')->nullable();
                $table->timestamp('verified_at');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('recovery_operations')) {
            Schema::create('recovery_operations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('backup_id')->constrained('backup_records')->cascadeOnDelete();
                $table->string('target_environment')->default('staging');
                $table->string('operation_type', 30)->default('drill'); // point_in_time, full_restore, drill
                $table->string('status', 20)->default('requested'); // requested, approved, in_progress, completed, aborted, failed
                $table->string('requested_by');
                $table->string('approved_by')->nullable();
                $table->json('logs')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        // 10. Support & Communication
        Schema::table('support_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('support_tickets', 'ticket_number')) {
                $table->string('ticket_number')->nullable()->after('id');
            }
            if (!Schema::hasColumn('support_tickets', 'category')) {
                $table->string('category', 30)->default('technical')->after('subject');
            }
            if (!Schema::hasColumn('support_tickets', 'assigned_admin_id')) {
                $table->foreignId('assigned_admin_id')->nullable()->constrained('super_admins')->nullOnDelete();
            }
            if (!Schema::hasColumn('support_tickets', 'last_reply_at')) {
                $table->timestamp('last_reply_at')->nullable();
            }
            if (!Schema::hasColumn('support_tickets', 'closed_at')) {
                $table->timestamp('closed_at')->nullable();
            }
        });

        Schema::table('support_ticket_replies', function (Blueprint $table) {
            if (!Schema::hasColumn('support_ticket_replies', 'sender_type')) {
                $table->string('sender_type', 20)->default('super_admin')->after('support_ticket_id');
            }
            if (!Schema::hasColumn('support_ticket_replies', 'is_internal_note')) {
                $table->boolean('is_internal_note')->default(false)->after('message');
            }
            if (!Schema::hasColumn('support_ticket_replies', 'attachments')) {
                $table->json('attachments')->nullable()->after('is_internal_note');
            }
        });

        if (!Schema::hasTable('notification_templates')) {
            Schema::create('notification_templates', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->string('channel', 20); // email, sms, in_app
                $table->string('subject')->nullable();
                $table->text('body');
                $table->json('variables')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('notification_logs')) {
            Schema::create('notification_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('template_id')->nullable()->constrained('notification_templates')->nullOnDelete();
                $table->string('recipient');
                $table->string('channel', 20);
                $table->string('subject')->nullable();
                $table->text('content');
                $table->string('status', 20)->default('sent'); // queued, sent, failed
                $table->text('error_message')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
            });
        }

        // 11. Developer Integrations, Webhooks, Reports
        Schema::table('api_keys', function (Blueprint $table) {
            if (!Schema::hasColumn('api_keys', 'key_prefix')) {
                $table->string('key_prefix', 12)->nullable()->after('key');
            }
            if (!Schema::hasColumn('api_keys', 'permissions')) {
                $table->json('permissions')->nullable()->after('key_prefix');
            }
            if (!Schema::hasColumn('api_keys', 'rate_limit_per_minute')) {
                $table->integer('rate_limit_per_minute')->default(60);
            }
        });

        if (!Schema::hasTable('webhook_endpoints')) {
            Schema::create('webhook_endpoints', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('target_url');
                $table->string('secret');
                $table->json('events');
                $table->boolean('is_active')->default(true);
                $table->integer('failure_count')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('webhook_delivery_logs')) {
            Schema::create('webhook_delivery_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('endpoint_id')->constrained('webhook_endpoints')->cascadeOnDelete();
                $table->string('event');
                $table->json('payload');
                $table->integer('response_code')->nullable();
                $table->text('response_body')->nullable();
                $table->string('status', 20)->default('pending'); // success, failed, pending
                $table->integer('attempt')->default(1);
                $table->timestamp('attempted_at');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('report_jobs')) {
            Schema::create('report_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('report_type'); // tenants, revenue, domains, storage, pdf_processing, security, audit
                $table->string('format', 10)->default('csv'); // csv, excel, pdf
                $table->json('filters')->nullable();
                $table->string('status', 20)->default('queued'); // queued, processing, completed, failed
                $table->string('file_path')->nullable();
                $table->decimal('file_size_kb', 10, 2)->default(0);
                $table->foreignId('generated_by_admin_id')->nullable()->constrained('super_admins')->nullOnDelete();
                $table->text('error')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Safe rollback
        Schema::dropIfExists('report_jobs');
        Schema::dropIfExists('webhook_delivery_logs');
        Schema::dropIfExists('webhook_endpoints');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('recovery_operations');
        Schema::dropIfExists('backup_verification_logs');
        Schema::dropIfExists('deployment_logs');
        Schema::dropIfExists('infrastructure_alerts');
        Schema::dropIfExists('infrastructure_health_logs');
        Schema::dropIfExists('infrastructure_services');
        Schema::dropIfExists('storage_usage_records');
        Schema::dropIfExists('pdf_processing_logs');
        Schema::dropIfExists('pdf_processing_jobs');
        Schema::dropIfExists('newspaper_editions');
        Schema::dropIfExists('branding_versions');
        Schema::dropIfExists('tenant_branding');
        Schema::dropIfExists('platform_branding');
        Schema::dropIfExists('payment_reconciliation_logs');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscription_invoices');
        Schema::dropIfExists('subscription_history');
        Schema::dropIfExists('tenant_subscriptions');
        Schema::dropIfExists('package_entitlements');
        Schema::dropIfExists('subscription_packages');
        Schema::dropIfExists('domain_activity_logs');
        Schema::dropIfExists('domain_ssl_certificates');
        Schema::dropIfExists('domain_dns_records');
        Schema::dropIfExists('tenant_provisioning_logs');
        Schema::dropIfExists('tenant_status_history');
        Schema::dropIfExists('tenant_admin_invitations');
        Schema::dropIfExists('tenant_configurations');
        Schema::dropIfExists('tenant_contacts');
        Schema::dropIfExists('super_admin_sessions');
        Schema::dropIfExists('super_admin_role_permissions');
        Schema::dropIfExists('super_admin_permissions');
        Schema::dropIfExists('super_admin_roles');
    }
};
