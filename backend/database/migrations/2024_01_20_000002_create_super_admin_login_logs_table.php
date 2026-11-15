<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('super_admin_login_logs')) {
            Schema::create('super_admin_login_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('super_admin_id')->nullable()->constrained('super_admins')->nullOnDelete();
                $table->string('email')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('status', 20)->default('success'); // success, failed, blocked
                $table->string('failure_reason')->nullable();
                $table->timestamp('attempted_at');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('super_admin_login_logs');
    }
};
