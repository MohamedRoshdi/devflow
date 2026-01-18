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
        if (Schema::hasTable('resource_alerts')) {
            return;
        }

        Schema::create('resource_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->enum('resource_type', ['cpu', 'memory', 'disk', 'load'])->default('cpu');
            $table->enum('threshold_type', ['above', 'below'])->default('above');
            $table->decimal('threshold_value', 8, 2);
            $table->json('notification_channels')->nullable()->comment('JSON array of notification channels (email, slack, discord)');
            $table->boolean('is_active')->default(true);
            $table->integer('cooldown_minutes')->default(15)->comment('Minimum minutes between alert triggers');
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();

            $table->index(['server_id', 'is_active']);
            $table->index(['resource_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_alerts');
    }
};
