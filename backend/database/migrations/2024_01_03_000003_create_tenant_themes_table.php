<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('theme_preset')->default('classic');
            $table->string('layout')->default('grid');
            $table->string('font_family')->default('Inter');
            $table->string('primary_color')->default('#2563eb');
            $table->string('secondary_color')->default('#1e293b');
            $table->string('accent_color')->default('#f59e0b');
            $table->json('features')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_themes');
    }
};
