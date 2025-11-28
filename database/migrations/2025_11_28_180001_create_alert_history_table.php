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
        if (Schema::hasTable('alert_history')) {
            return;
        }

        Schema::create('alert_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_alert_id')->constrained()->onDelete('cascade');
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->enum('resource_type', ['cpu', 'memory', 'disk', 'load']);
            $table->decimal('current_value', 8, 2);
            $table->decimal('threshold_value', 8, 2);
            $table->enum('status', ['triggered', 'resolved'])->default('triggered');
            $table->text('message');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index(['server_id', 'created_at']);
            $table->index(['resource_alert_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_history');
    }
};
