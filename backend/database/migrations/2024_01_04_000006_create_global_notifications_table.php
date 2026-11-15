<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_notifications', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['announcement', 'expiry_alert', 'maintenance_alert']);
            $table->string('title');
            $table->text('message');
            $table->json('channels')->nullable();
            $table->boolean('is_sent')->default(false);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_notifications');
    }
};
