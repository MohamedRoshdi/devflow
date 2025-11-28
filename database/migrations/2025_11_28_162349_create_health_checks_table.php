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
        Schema::create('health_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('server_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('check_type', ['http', 'tcp', 'ping', 'ssl_expiry'])->default('http');
            $table->string('target_url', 500)->nullable();
            $table->integer('expected_status')->default(200);
            $table->integer('interval_minutes')->default(5);
            $table->integer('timeout_seconds')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_check_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->integer('consecutive_failures')->default(0);
            $table->enum('status', ['healthy', 'degraded', 'down', 'unknown'])->default('unknown');
            $table->timestamps();

            $table->index(['project_id', 'is_active']);
            $table->index(['server_id', 'is_active']);
            $table->index(['status', 'is_active']);
            $table->index('last_check_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_checks');
    }
};
