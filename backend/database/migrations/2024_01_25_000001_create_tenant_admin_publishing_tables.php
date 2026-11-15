<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Newspaper Profiles (Editorial & Contact Details for the Publication)
        if (!Schema::hasTable('newspaper_profiles')) {
            Schema::create('newspaper_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('newspaper_name');
                $table->string('company_name')->nullable();
                $table->string('publisher_name')->nullable();
                $table->string('editor_name')->nullable();
                $table->string('contact_person')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('whatsapp')->nullable();
                $table->text('address')->nullable();
                $table->string('state')->nullable();
                $table->string('district')->nullable();
                $table->string('mandal')->nullable();
                $table->string('pincode', 10)->nullable();
                $table->string('language', 10)->default('te'); // te (Telugu), en, hi, etc.
                $table->string('logo_url')->nullable();
                $table->string('favicon_url')->nullable();
                $table->text('about_newspaper')->nullable();
                $table->json('social_links')->nullable();
                $table->timestamps();
                $table->unique('tenant_id');
            });
        }

        // 2. Extend newspaper_editions table with full publishing columns
        Schema::table('newspaper_editions', function (Blueprint $table) {
            if (!Schema::hasColumn('newspaper_editions', 'edition_title')) {
                $table->string('edition_title')->nullable()->after('edition_name');
            }
            if (!Schema::hasColumn('newspaper_editions', 'edition_language')) {
                $table->string('edition_language', 10)->default('te')->after('edition_title');
            }
            if (!Schema::hasColumn('newspaper_editions', 'category')) {
                $table->string('category', 50)->default('Main Edition')->after('edition_language');
            }
            if (!Schema::hasColumn('newspaper_editions', 'description')) {
                $table->text('description')->nullable()->after('category');
            }
            if (!Schema::hasColumn('newspaper_editions', 'thumbnail_path')) {
                $table->string('thumbnail_path')->nullable()->after('description');
            }
            if (!Schema::hasColumn('newspaper_editions', 'original_pdf_path')) {
                $table->string('original_pdf_path')->nullable()->after('thumbnail_path');
            }
            if (!Schema::hasColumn('newspaper_editions', 'checksum_sha256')) {
                $table->string('checksum_sha256', 64)->nullable()->after('original_pdf_path');
            }
            if (!Schema::hasColumn('newspaper_editions', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('newspaper_editions', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('scheduled_at');
            }
            if (!Schema::hasColumn('newspaper_editions', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('published_at');
            }
            if (!Schema::hasColumn('newspaper_editions', 'created_by_user_id')) {
                $table->foreignId('created_by_user_id')->nullable()->after('archived_at')->constrained('users')->nullOnDelete();
            }
        });

        // 3. Edition Pages (Individual Page Images, Thumbnails, Sorting, and Rotation)
        if (!Schema::hasTable('edition_pages')) {
            Schema::create('edition_pages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('edition_id')->constrained('newspaper_editions')->cascadeOnDelete();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->integer('page_number');
                $table->string('page_title')->nullable();
                $table->string('image_path');
                $table->string('thumbnail_path')->nullable();
                $table->integer('width')->default(1200);
                $table->integer('height')->default(1800);
                $table->integer('rotation')->default(0); // 0, 90, 180, 270
                $table->boolean('is_visible')->default(true);
                $table->integer('sort_order')->default(1);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['edition_id', 'page_number']);
                $table->index(['tenant_id', 'edition_id']);
            });
        }

        // 4. PDF Uploads (Audit & Staging for Uploaded Files)
        if (!Schema::hasTable('pdf_uploads')) {
            Schema::create('pdf_uploads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('edition_id')->nullable()->constrained('newspaper_editions')->nullOnDelete();
                $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('original_filename');
                $table->string('stored_filename');
                $table->string('file_path');
                $table->unsignedBigInteger('file_size');
                $table->string('mime_type')->default('application/pdf');
                $table->integer('page_count')->default(0);
                $table->string('checksum_sha256', 64)->nullable();
                $table->string('status', 30)->default('uploaded'); // uploaded, processing, completed, failed
                $table->text('error_message')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
            });
        }

        // 5. Publishing Schedules
        if (!Schema::hasTable('publishing_schedules')) {
            Schema::create('publishing_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('edition_id')->constrained('newspaper_editions')->cascadeOnDelete();
                $table->foreignId('scheduled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('scheduled_at');
                $table->string('timezone', 50)->default('Asia/Kolkata');
                $table->string('status', 30)->default('pending'); // pending, executed, cancelled
                $table->timestamp('published_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
            });
        }

        // 6. Newspaper Appearance Templates
        if (!Schema::hasTable('newspaper_templates')) {
            Schema::create('newspaper_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete(); // null = system default
                $table->string('name');
                $table->string('slug');
                $table->text('description')->nullable();
                $table->string('thumbnail_url')->nullable();
                $table->string('version', 20)->default('1.0.0');
                $table->json('layout_config')->nullable();
                $table->json('color_palette')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }

        // 7. Tenant Notifications (Internal Inbox for Processing Alerts, Quota Warnings, Status Updates)
        if (!Schema::hasTable('tenant_notifications')) {
            Schema::create('tenant_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('type', 50); // pdf_processed, pdf_failed, edition_published, quota_warning, support_reply
                $table->string('title');
                $table->text('message');
                $table->json('data')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'is_read']);
            });
        }

        // 8. Tenant Activity Logs (Tamper-evident audit of staff actions)
        if (!Schema::hasTable('tenant_activity_logs')) {
            Schema::create('tenant_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 50); // login, edition_created, edition_published, page_rotated, etc.
                $table->string('module', 50);
                $table->text('description');
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'action']);
            });
        }

        // 9. Tenant Upgrade Requests (Requests sent to Super Admin for plan changes)
        if (!Schema::hasTable('tenant_upgrade_requests')) {
            Schema::create('tenant_upgrade_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('target_package_id')->constrained('subscription_packages')->cascadeOnDelete();
                $table->text('notes')->nullable();
                $table->string('status', 30)->default('pending'); // pending, approved, rejected
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_upgrade_requests');
        Schema::dropIfExists('tenant_activity_logs');
        Schema::dropIfExists('tenant_notifications');
        Schema::dropIfExists('newspaper_templates');
        Schema::dropIfExists('publishing_schedules');
        Schema::dropIfExists('pdf_uploads');
        Schema::dropIfExists('edition_pages');
        Schema::dropIfExists('newspaper_profiles');
    }
};
