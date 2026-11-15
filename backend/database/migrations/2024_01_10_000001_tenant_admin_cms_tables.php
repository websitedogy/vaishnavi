<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description')->nullable();
            $table->string('icon')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();
            $table->unique(['tenant_id', 'slug']);
        });

        Schema::table('news_posts', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('title');
            $table->longText('content')->nullable()->after('slug');
            $table->text('excerpt')->nullable()->after('content');
            $table->foreignId('category_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            $table->foreignId('author_id')->nullable()->after('category_id')->constrained('users')->nullOnDelete();
            $table->string('featured_image')->nullable()->after('excerpt');
            $table->boolean('is_breaking')->default(false)->after('status');
            $table->boolean('is_featured')->default(false)->after('is_breaking');
            $table->timestamp('scheduled_at')->nullable()->after('is_featured');
            $table->timestamp('published_at')->nullable()->after('scheduled_at');
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('locale', 10)->default('en');
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->json('autosave_data')->nullable();
            $table->foreignId('duplicated_from_id')->nullable()->constrained('news_posts')->nullOnDelete();
        });

        Schema::create('article_tag', function (Blueprint $table) {
            $table->foreignId('news_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['news_post_id', 'tag_id']);
        });

        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('path');
            $table->string('disk')->default('public');
            $table->string('type', 20)->default('image');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('thumbnail_path')->nullable();
            $table->string('alt_text')->nullable();
            $table->timestamps();
        });

        Schema::create('article_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('news_post_id')->constrained()->cascadeOnDelete();
            $table->string('author_name');
            $table->string('author_email')->nullable();
            $table->text('body');
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
        });

        Schema::create('staff_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('description');
            $table->nullableMorphs('subject');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_activity_logs');
        Schema::dropIfExists('article_comments');
        Schema::dropIfExists('media_files');
        Schema::dropIfExists('article_tag');
        Schema::table('news_posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('duplicated_from_id');
            $table->dropConstrainedForeignId('category_id');
            $table->dropConstrainedForeignId('author_id');
            $table->dropColumn([
                'slug', 'content', 'excerpt', 'featured_image', 'is_breaking', 'is_featured',
                'scheduled_at', 'published_at', 'meta_title', 'meta_description', 'locale',
                'approval_status', 'autosave_data',
            ]);
        });
        Schema::dropIfExists('tags');
        Schema::dropIfExists('categories');
    }
};
