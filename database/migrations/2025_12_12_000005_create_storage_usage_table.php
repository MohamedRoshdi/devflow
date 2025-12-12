<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration creates the storage_usage table for tracking storage
     * analytics across all projects as specified in the CLAUDE.md documentation.
     */
    public function up(): void
    {
        if (! Schema::hasTable('storage_usage')) {
            Schema::create('storage_usage', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')
                    ->constrained('projects')
                    ->onDelete('cascade');
                $table->string('storage_driver', 50)->comment('Storage driver used (local, s3, gcs, etc.)');
                $table->integer('total_files')->default(0)->comment('Total number of files');
                $table->bigInteger('total_size_bytes')->default(0)->comment('Total size in bytes');
                $table->bigInteger('cache_size_bytes')->default(0)->comment('Cache size in bytes');
                $table->bigInteger('logs_size_bytes')->default(0)->comment('Logs size in bytes');
                $table->bigInteger('uploads_size_bytes')->default(0)->comment('Uploads size in bytes');
                $table->bigInteger('backup_size_bytes')->default(0)->comment('Backup size in bytes');
                $table->timestamp('last_cleanup_at')->nullable()->comment('Last cleanup timestamp');
                $table->timestamp('recorded_at')->useCurrent()->comment('When this usage was recorded');

                // Indexes for common queries
                $table->index(['project_id', 'recorded_at'], 'idx_usage_project_date');
                $table->index('storage_driver', 'idx_usage_driver');
                $table->index('recorded_at', 'idx_usage_recorded');
                $table->index('total_size_bytes', 'idx_usage_size');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage_usage');
    }
};
