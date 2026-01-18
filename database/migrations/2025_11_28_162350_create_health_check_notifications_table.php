<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('health_check_notifications')) {
            return;
        }

        Schema::create('health_check_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_check_id')->constrained()->onDelete('cascade');
            $table->foreignId('notification_channel_id')->constrained()->onDelete('cascade');
            $table->boolean('notify_on_failure')->default(true);
            $table->boolean('notify_on_recovery')->default(true);

            $table->unique(['health_check_id', 'notification_channel_id'], 'health_check_channel_unique');
            $table->index('health_check_id');
            $table->index('notification_channel_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_check_notifications');
    }
};
