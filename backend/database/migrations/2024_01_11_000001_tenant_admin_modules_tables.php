<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('group', 50);
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'group']);
        });

        Schema::table('advertisements', function (Blueprint $table) {
            $table->string('ad_type', 20)->default('banner')->after('placement');
            $table->string('video_url')->nullable()->after('image_url');
            $table->string('adsense_slot')->nullable()->after('video_url');
        });

        Schema::table('article_comments', function (Blueprint $table) {
            $table->boolean('is_spam')->default(false)->after('is_approved');
            $table->string('ip_address', 45)->nullable()->after('author_email');
        });

        Schema::create('blocked_comment_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('email')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('polls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('question');
            $table->json('options');
            $table->json('votes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('closes_at')->nullable();
            $table->timestamps();
        });

        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('subscribed_at')->useCurrent();
            $table->timestamps();
            $table->unique(['tenant_id', 'email']);
        });

        Schema::create('newsletter_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('subject');
            $table->longText('body');
            $table->string('status', 20)->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('open_count')->default(0);
            $table->timestamps();
        });

        Schema::create('push_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('topic')->nullable();
            $table->string('channel', 20)->default('browser');
            $table->string('status', 20)->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('subscriber_count')->default(0);
            $table->timestamps();
        });

        Schema::create('push_subscribers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('endpoint')->nullable();
            $table->string('topic')->nullable();
            $table->timestamps();
        });

        Schema::create('readers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('password')->nullable();
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_banned')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'email']);
        });

        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->string('interval', 20)->default('monthly');
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->unsignedTinyInteger('discount_percent')->default(10);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('tenant_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('key_prefix', 16);
            $table->string('key_hash');
            $table->json('webhooks')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('article_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_post_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();
            $table->unique(['news_post_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_reactions');
        Schema::dropIfExists('tenant_api_keys');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('subscription_plans');
        Schema::dropIfExists('readers');
        Schema::dropIfExists('push_subscribers');
        Schema::dropIfExists('push_notifications');
        Schema::dropIfExists('newsletter_campaigns');
        Schema::dropIfExists('newsletter_subscribers');
        Schema::dropIfExists('polls');
        Schema::dropIfExists('blocked_comment_users');
        Schema::table('article_comments', function (Blueprint $table) {
            $table->dropColumn(['is_spam', 'ip_address']);
        });
        Schema::table('advertisements', function (Blueprint $table) {
            $table->dropColumn(['ad_type', 'video_url', 'adsense_slot']);
        });
        Schema::dropIfExists('tenant_settings');
    }
};
