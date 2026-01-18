<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('filename', 500);
            $table->enum('type', ['full', 'incremental'])->default('full');
            $table->string('source_path', 500)->comment('Path on server like /var/www/project/storage');
            $table->enum('storage_disk', ['local', 's3', 'gcs', 'azure'])->default('local');
            $table->string('storage_path', 500)->comment('Path in storage disk');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedInteger('files_count')->default(0);
            $table->string('checksum', 64)->nullable()->comment('SHA256 checksum');
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('manifest')->nullable()->comment('List of files included in backup');
            $table->json('exclude_patterns')->nullable()->comment('Patterns that were excluded');
            $table->foreignId('parent_backup_id')->nullable()->constrained('file_backups')->cascadeOnDelete()->comment('For incremental backups');
            $table->timestamps();

            $table->index(['project_id', 'created_at']);
            $table->index(['project_id', 'type']);
            $table->index('status');
            $table->index('parent_backup_id');
            $table->index('checksum');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_backups');
    }
};
