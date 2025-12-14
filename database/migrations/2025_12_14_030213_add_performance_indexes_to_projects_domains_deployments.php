<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Performance indexes for common query patterns:
     * - User-scoped project queries
     * - Team-scoped project queries
     * - Domain SSL renewal queries
     * - Primary domain lookups
     * - Deployment stats and filtering
     */
    public function up(): void
    {
        // Projects table indexes
        Schema::table('projects', function (Blueprint $table) {
            // For user-scoped project queries with status filtering
            $table->index(['user_id', 'status'], 'projects_user_id_status_index');

            // For team-scoped project queries with status filtering
            $table->index(['team_id', 'status'], 'projects_team_id_status_index');
        });

        // Domains table indexes
        Schema::table('domains', function (Blueprint $table) {
            // For "domains needing SSL renewal" queries
            $table->index(['project_id', 'ssl_enabled'], 'domains_project_id_ssl_enabled_index');

            // For primary domain lookup in health checks
            $table->index(['project_id', 'is_primary'], 'domains_project_id_is_primary_index');
        });

        // Deployments table indexes
        Schema::table('deployments', function (Blueprint $table) {
            // For deployment stats queries by user
            $table->index('user_id', 'deployments_user_id_index');

            // For deployment filtering by trigger type
            $table->index('triggered_by', 'deployments_triggered_by_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('projects_user_id_status_index');
            $table->dropIndex('projects_team_id_status_index');
        });

        Schema::table('domains', function (Blueprint $table) {
            $table->dropIndex('domains_project_id_ssl_enabled_index');
            $table->dropIndex('domains_project_id_is_primary_index');
        });

        Schema::table('deployments', function (Blueprint $table) {
            $table->dropIndex('deployments_user_id_index');
            $table->dropIndex('deployments_triggered_by_index');
        });
    }
};
