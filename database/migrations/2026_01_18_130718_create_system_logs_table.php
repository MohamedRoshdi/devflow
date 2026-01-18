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
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('log_type', 50)->default('system'); // syslog, auth, kern, docker, nginx, php, mysql, etc.
            $table->string('level', 20)->default('info'); // emergency, alert, critical, error, warning, notice, info, debug
            $table->string('source')->nullable(); // Which service/process generated the log
            $table->text('message');
            $table->json('metadata')->nullable(); // Additional structured data
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('logged_at')->useCurrent();
            $table->timestamps();

            // Indexes for better query performance
            $table->index('server_id');
            $table->index('log_type');
            $table->index('level');
            $table->index('logged_at');
            $table->index(['server_id', 'log_type']);
            $table->index(['log_type', 'level']);
            $table->index(['server_id', 'logged_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
