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
        Schema::create('log_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('pattern'); // Regex or text pattern to match
            $table->string('log_type')->nullable(); // Specific log type to monitor
            $table->string('log_level')->nullable(); // Specific log level to monitor
            $table->boolean('is_regex')->default(false);
            $table->boolean('case_sensitive')->default(false);
            $table->integer('threshold')->default(1); // How many occurrences before alerting
            $table->integer('time_window')->default(60); // Time window in seconds
            $table->json('notification_channels')->nullable(); // email, slack, webhook
            $table->json('notification_config')->nullable(); // Channel-specific config
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('trigger_count')->default(0);
            $table->timestamps();

            $table->index('server_id');
            $table->index('is_active');
            $table->index(['log_type', 'log_level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_alerts');
    }
};
