<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds post-editor columns if earlier migrations were not run.
 */
return new class extends Migration
{
    public function up(): void
    {
        $add = function (string $column, callable $callback) {
            if (! Schema::hasColumn('news_posts', $column)) {
                Schema::table('news_posts', $callback);
            }
        };

        $add('subtitle', fn (Blueprint $t) => $t->string('subtitle')->nullable());
        $add('custom_title', fn (Blueprint $t) => $t->string('custom_title')->nullable());
        $add('is_trending', fn (Blueprint $t) => $t->boolean('is_trending')->default(false));
        $add('focus_keyword', fn (Blueprint $t) => $t->string('focus_keyword')->nullable());
        $add('is_pillar_content', fn (Blueprint $t) => $t->boolean('is_pillar_content')->default(false));
        $add('og_title', fn (Blueprint $t) => $t->string('og_title')->nullable());
        $add('og_description', fn (Blueprint $t) => $t->string('og_description')->nullable());
        $add('og_image', fn (Blueprint $t) => $t->string('og_image')->nullable());
        $add('schema_type', fn (Blueprint $t) => $t->string('schema_type', 40)->default('article'));
        $add('seo_noindex', fn (Blueprint $t) => $t->boolean('seo_noindex')->default(false));
        $add('canonical_url', fn (Blueprint $t) => $t->string('canonical_url')->nullable());
    }

    public function down(): void
    {
        // Intentionally empty — do not drop columns that may have existed before this migration.
    }
};
