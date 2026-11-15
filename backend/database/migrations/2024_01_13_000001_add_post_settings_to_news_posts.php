<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_posts', function (Blueprint $table) {
            $table->string('custom_title')->nullable()->after('subtitle');
            $table->boolean('is_trending')->default(false)->after('is_featured');
            $table->string('focus_keyword')->nullable()->after('meta_description');
        });
    }

    public function down(): void
    {
        Schema::table('news_posts', function (Blueprint $table) {
            $table->dropColumn(['custom_title', 'is_trending', 'focus_keyword']);
        });
    }
};
