<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_posts', function (Blueprint $table) {
            $table->boolean('is_pillar_content')->default(false)->after('focus_keyword');
            $table->string('og_title')->nullable()->after('is_pillar_content');
            $table->string('og_description')->nullable()->after('og_title');
            $table->string('og_image')->nullable()->after('og_description');
            $table->string('schema_type', 40)->default('article')->after('og_image');
            $table->boolean('seo_noindex')->default(false)->after('schema_type');
            $table->string('canonical_url')->nullable()->after('seo_noindex');
        });
    }

    public function down(): void
    {
        Schema::table('news_posts', function (Blueprint $table) {
            $table->dropColumn([
                'is_pillar_content', 'og_title', 'og_description', 'og_image',
                'schema_type', 'seo_noindex', 'canonical_url',
            ]);
        });
    }
};
