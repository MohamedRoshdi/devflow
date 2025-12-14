<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        if (! $this->indexExists('projects', 'projects_user_id_status_index')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->index(['user_id', 'status'], 'projects_user_id_status_index');
            });
        }

        if (! $this->indexExists('projects', 'projects_team_id_status_index')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->index(['team_id', 'status'], 'projects_team_id_status_index');
            });
        }

        // Domains table indexes
        if (! $this->indexExists('domains', 'domains_project_id_ssl_enabled_index')) {
            Schema::table('domains', function (Blueprint $table) {
                $table->index(['project_id', 'ssl_enabled'], 'domains_project_id_ssl_enabled_index');
            });
        }

        if (! $this->indexExists('domains', 'domains_project_id_is_primary_index')) {
            Schema::table('domains', function (Blueprint $table) {
                $table->index(['project_id', 'is_primary'], 'domains_project_id_is_primary_index');
            });
        }

        // Deployments table indexes
        if (! $this->indexExists('deployments', 'deployments_user_id_index')) {
            Schema::table('deployments', function (Blueprint $table) {
                $table->index('user_id', 'deployments_user_id_index');
            });
        }

        if (! $this->indexExists('deployments', 'deployments_triggered_by_index')) {
            Schema::table('deployments', function (Blueprint $table) {
                $table->index('triggered_by', 'deployments_triggered_by_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->indexExists('projects', 'projects_user_id_status_index')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropIndex('projects_user_id_status_index');
            });
        }

        if ($this->indexExists('projects', 'projects_team_id_status_index')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropIndex('projects_team_id_status_index');
            });
        }

        if ($this->indexExists('domains', 'domains_project_id_ssl_enabled_index')) {
            Schema::table('domains', function (Blueprint $table) {
                $table->dropIndex('domains_project_id_ssl_enabled_index');
            });
        }

        if ($this->indexExists('domains', 'domains_project_id_is_primary_index')) {
            Schema::table('domains', function (Blueprint $table) {
                $table->dropIndex('domains_project_id_is_primary_index');
            });
        }

        if ($this->indexExists('deployments', 'deployments_user_id_index')) {
            Schema::table('deployments', function (Blueprint $table) {
                $table->dropIndex('deployments_user_id_index');
            });
        }

        if ($this->indexExists('deployments', 'deployments_triggered_by_index')) {
            Schema::table('deployments', function (Blueprint $table) {
                $table->dropIndex('deployments_triggered_by_index');
            });
        }
    }

    /**
     * Check if an index exists on a table (database agnostic).
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select(
                "SELECT name FROM sqlite_master WHERE type='index' AND tbl_name = ? AND name = ?",
                [$table, $indexName]
            );
            return count($indexes) > 0;
        }

        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);

        return count($indexes) > 0;
    }
};
