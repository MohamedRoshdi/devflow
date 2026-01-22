<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_command_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action', 100)->index(); // e.g., 'reboot', 'restart_service', 'deploy'
            $table->text('command')->nullable(); // The actual command executed
            $table->enum('execution_type', ['local', 'ssh'])->default('ssh');
            $table->enum('status', ['pending', 'running', 'success', 'failed'])->default('pending');
            $table->text('output')->nullable();
            $table->text('error_output')->nullable();
            $table->integer('exit_code')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->json('metadata')->nullable(); // Additional context
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['server_id', 'created_at']);
            $table->index(['action', 'status']);
            $table->index('execution_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_command_histories');
    }
};
