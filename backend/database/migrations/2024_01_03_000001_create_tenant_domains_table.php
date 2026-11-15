<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['subdomain', 'custom']);
            $table->string('host');
            $table->boolean('is_primary')->default(false);
            $table->enum('ssl_status', ['pending', 'provisioning', 'active', 'failed', 'expired'])->default('pending');
            $table->boolean('ssl_auto')->default(true);
            $table->timestamp('ssl_issued_at')->nullable();
            $table->timestamp('ssl_expires_at')->nullable();
            $table->timestamps();

            $table->unique(['type', 'host']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_domains');
    }
};
