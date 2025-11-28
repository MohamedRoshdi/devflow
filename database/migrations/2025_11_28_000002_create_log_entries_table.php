<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('source', ['nginx', 'php', 'laravel', 'mysql', 'system', 'docker', 'other'])->default('other');
            $table->enum('level', ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'])->default('info');
            $table->text('message');
            $table->json('context')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->integer('line_number')->nullable();
            $table->timestamp('logged_at');
            $table->timestamps();

            $table->index('server_id');
            $table->index('project_id');
            $table->index('source');
            $table->index('level');
            $table->index('logged_at');
            $table->index(['server_id', 'logged_at']);
            $table->index(['project_id', 'logged_at']);
            $table->index(['source', 'level', 'logged_at']);
            $table->fullText(['message']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_entries');
    }
};
