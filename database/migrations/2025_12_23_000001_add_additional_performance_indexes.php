<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Additional performance indexes identified in the code analysis.
 *
 * These indexes optimize:
 * - Multi-tenant queries (team_id filtering)
 * - User deployment history
 * - Active deployments timeline
 * - Server health monitoring queries
 */
return new class extends Migration
{
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

        if ($driver === 'pgsql') {
            $indexes = DB::select(
                'SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?',
                [$table, $indexName]
            );

            return count($indexes) > 0;
        }

        // MySQL/MariaDB
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);

        return count($indexes) > 0;
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add team_id indexes for multi-tenant queries on projects
        if (Schema::hasTable('projects') && Schema::hasColumn('projects', 'team_id')) {
            Schema::table('projects', function (Blueprint $table) {
                // Team filtering with status and updated_at (common dashboard query)
                if (! $this->indexExists('projects', 'projects_team_id_status_updated_at_index')) {
                    $table->index(['team_id', 'status', 'updated_at'], 'projects_team_id_status_updated_at_index');
                }
                // Framework filtering (project type queries)
                if (! $this->indexExists('projects', 'projects_framework_status_index')) {
                    $table->index(['framework', 'status'], 'projects_framework_status_index');
                }
                // User's projects with status
                if (! $this->indexExists('projects', 'projects_user_id_status_index')) {
                    $table->index(['user_id', 'status'], 'projects_user_id_status_index');
                }
            });
        }

        // Add user and status indexes on deployments
        if (Schema::hasTable('deployments')) {
            Schema::table('deployments', function (Blueprint $table) {
                // User deployment history
                if (! $this->indexExists('deployments', 'deployments_user_id_created_at_index')) {
                    $table->index(['user_id', 'created_at'], 'deployments_user_id_created_at_index');
                }
                // Active deployments timeline (for dashboard)
                if (! $this->indexExists('deployments', 'deployments_status_created_at_index')) {
                    $table->index(['status', 'created_at'], 'deployments_status_created_at_index');
                }
                // Triggered by filter
                if (! $this->indexExists('deployments', 'deployments_triggered_by_status_index')) {
                    $table->index(['triggered_by', 'status'], 'deployments_triggered_by_status_index');
                }
            });
        }

        // Add team_id and health monitoring indexes on servers
        if (Schema::hasTable('servers')) {
            Schema::table('servers', function (Blueprint $table) {
                // Team filtering (multi-tenant server queries)
                if (Schema::hasColumn('servers', 'team_id') && ! $this->indexExists('servers', 'servers_team_id_status_index')) {
                    $table->index(['team_id', 'status'], 'servers_team_id_status_index');
                }
                // Health monitoring queries
                if (Schema::hasColumn('servers', 'last_ping_at') && ! $this->indexExists('servers', 'servers_status_last_ping_at_index')) {
                    $table->index(['status', 'last_ping_at'], 'servers_status_last_ping_at_index');
                }
                // User's servers with status
                if (! $this->indexExists('servers', 'servers_user_id_status_index')) {
                    $table->index(['user_id', 'status'], 'servers_user_id_status_index');
                }
            });
        }

        // Add indexes for webhook secret lookups
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                // Webhook enabled projects (for timing-safe lookup)
                if (Schema::hasColumn('projects', 'auto_deploy') && ! $this->indexExists('projects', 'projects_auto_deploy_webhook_secret_index')) {
                    $table->index(['auto_deploy'], 'projects_auto_deploy_webhook_secret_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                if ($this->indexExists('projects', 'projects_team_id_status_updated_at_index')) {
                    $table->dropIndex('projects_team_id_status_updated_at_index');
                }
                if ($this->indexExists('projects', 'projects_framework_status_index')) {
                    $table->dropIndex('projects_framework_status_index');
                }
                if ($this->indexExists('projects', 'projects_user_id_status_index')) {
                    $table->dropIndex('projects_user_id_status_index');
                }
                if ($this->indexExists('projects', 'projects_auto_deploy_webhook_secret_index')) {
                    $table->dropIndex('projects_auto_deploy_webhook_secret_index');
                }
            });
        }

        if (Schema::hasTable('deployments')) {
            Schema::table('deployments', function (Blueprint $table) {
                if ($this->indexExists('deployments', 'deployments_user_id_created_at_index')) {
                    $table->dropIndex('deployments_user_id_created_at_index');
                }
                if ($this->indexExists('deployments', 'deployments_status_created_at_index')) {
                    $table->dropIndex('deployments_status_created_at_index');
                }
                if ($this->indexExists('deployments', 'deployments_triggered_by_status_index')) {
                    $table->dropIndex('deployments_triggered_by_status_index');
                }
            });
        }

        if (Schema::hasTable('servers')) {
            Schema::table('servers', function (Blueprint $table) {
                if ($this->indexExists('servers', 'servers_team_id_status_index')) {
                    $table->dropIndex('servers_team_id_status_index');
                }
                if ($this->indexExists('servers', 'servers_status_last_ping_at_index')) {
                    $table->dropIndex('servers_status_last_ping_at_index');
                }
                if ($this->indexExists('servers', 'servers_user_id_status_index')) {
                    $table->dropIndex('servers_user_id_status_index');
                }
            });
        }
    }
};
