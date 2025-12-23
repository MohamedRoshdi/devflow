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
     * This migration adds missing columns to the projects table as specified
     * in the CLAUDE.md documentation for DevFlow Pro.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Add storage_driver enum column if it doesn't exist
            if (! Schema::hasColumn('projects', 'storage_driver')) {
                $table->enum('storage_driver', ['local', 's3', 'gcs', 'dropbox', 'azure'])
                    ->default('local')
                    ->after('env_variables');
            }

            // Add storage_config JSON column if it doesn't exist
            if (! Schema::hasColumn('projects', 'storage_config')) {
                $table->json('storage_config')
                    ->nullable()
                    ->after('storage_driver');
            }

            // Add docker_compose_path column if it doesn't exist
            if (! Schema::hasColumn('projects', 'docker_compose_path')) {
                $table->string('docker_compose_path', 500)
                    ->default('docker-compose.yml')
                    ->after('storage_config');
            }

            // Add deployment_script column if it doesn't exist
            if (! Schema::hasColumn('projects', 'deployment_script')) {
                $table->text('deployment_script')
                    ->nullable()
                    ->after('docker_compose_path');
            }

            // Add health_check_interval column if it doesn't exist
            if (! Schema::hasColumn('projects', 'health_check_interval')) {
                $table->integer('health_check_interval')
                    ->default(300)
                    ->comment('Health check interval in seconds')
                    ->after('health_check_url');
            }

            // Add backup_enabled column if it doesn't exist
            if (! Schema::hasColumn('projects', 'backup_enabled')) {
                $table->boolean('backup_enabled')
                    ->default(true)
                    ->after('health_check_interval');
            }

            // Add monitoring_enabled column if it doesn't exist
            if (! Schema::hasColumn('projects', 'monitoring_enabled')) {
                $table->boolean('monitoring_enabled')
                    ->default(true)
                    ->after('backup_enabled');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Drop columns in reverse order
            if (Schema::hasColumn('projects', 'monitoring_enabled')) {
                $table->dropColumn('monitoring_enabled');
            }
            if (Schema::hasColumn('projects', 'backup_enabled')) {
                $table->dropColumn('backup_enabled');
            }
            if (Schema::hasColumn('projects', 'health_check_interval')) {
                $table->dropColumn('health_check_interval');
            }
            if (Schema::hasColumn('projects', 'deployment_script')) {
                $table->dropColumn('deployment_script');
            }
            if (Schema::hasColumn('projects', 'docker_compose_path')) {
                $table->dropColumn('docker_compose_path');
            }
            if (Schema::hasColumn('projects', 'storage_config')) {
                $table->dropColumn('storage_config');
            }
            if (Schema::hasColumn('projects', 'storage_driver')) {
                $table->dropColumn('storage_driver');
            }
        });
    }
};
