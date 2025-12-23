<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('database_backups')) {
            Schema::create('database_backups', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('server_id')->constrained()->cascadeOnDelete();
                $table->enum('database_type', ['mysql', 'postgresql', 'sqlite'])->default('mysql');
                $table->string('database_name');
                $table->string('file_name', 500);
                $table->string('file_path', 500);
                $table->unsignedBigInteger('file_size')->nullable();
                $table->enum('storage_disk', ['local', 's3'])->default('local');
                $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['project_id', 'created_at']);
                $table->index('status');
                $table->index('database_name');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('database_backups');
    }
};
