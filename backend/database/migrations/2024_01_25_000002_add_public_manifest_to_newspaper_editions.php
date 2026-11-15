<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newspaper_editions', function (Blueprint $table) {
            if (!Schema::hasColumn('newspaper_editions', 'public_manifest')) {
                $table->json('public_manifest')->nullable()->after('status');
            }
            if (!Schema::hasColumn('newspaper_editions', 'manifest_generated_at')) {
                $table->timestamp('manifest_generated_at')->nullable()->after('public_manifest');
            }
        });
    }

    public function down(): void
    {
        Schema::table('newspaper_editions', function (Blueprint $table) {
            if (Schema::hasColumn('newspaper_editions', 'manifest_generated_at')) {
                $table->dropColumn('manifest_generated_at');
            }
            if (Schema::hasColumn('newspaper_editions', 'public_manifest')) {
                $table->dropColumn('public_manifest');
            }
        });
    }
};
