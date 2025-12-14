<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add composite indexes for common query patterns with status filtering and time-based sorting.
 *
 * These 3-column composite indexes optimize queries that:
 * - Filter by foreign key (project_id, server_id, user_id)
 * - Filter by status
 * - Sort by created_at (most recent first)
 */
return new class extends Migration
{
    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);

        return count($indexes) > 0;
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Deployments: (project_id, status, created_at) - for filtered deployment history
        if (Schema::hasTable('deployments')) {
            if (! $this->indexExists('deployments', 'deployments_project_status_created_idx')) {
                Schema::table('deployments', function (Blueprint $table) {
                    $table->index(
                        ['project_id', 'status', 'created_at'],
                        'deployments_project_status_created_idx'
                    );
                });
            }
        }

        // Projects: (user_id, status, created_at) - for user project listing with filters
        if (Schema::hasTable('projects')) {
            if (! $this->indexExists('projects', 'projects_user_status_created_idx')) {
                Schema::table('projects', function (Blueprint $table) {
                    $table->index(
                        ['user_id', 'status', 'created_at'],
                        'projects_user_status_created_idx'
                    );
                });
            }
        }

        // Health checks: (server_id, status, created_at) - for health check history
        if (Schema::hasTable('health_checks')) {
            if (! $this->indexExists('health_checks', 'health_checks_server_status_created_idx')) {
                Schema::table('health_checks', function (Blueprint $table) {
                    $table->index(
                        ['server_id', 'status', 'created_at'],
                        'health_checks_server_status_created_idx'
                    );
                });
            }
        }

        // Audit logs: (user_id, action, created_at) - for audit log filtering by user/action
        if (Schema::hasTable('audit_logs')) {
            if (! $this->indexExists('audit_logs', 'audit_logs_user_action_created_idx')) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->index(
                        ['user_id', 'action', 'created_at'],
                        'audit_logs_user_action_created_idx'
                    );
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('deployments')) {
            if ($this->indexExists('deployments', 'deployments_project_status_created_idx')) {
                Schema::table('deployments', function (Blueprint $table) {
                    $table->dropIndex('deployments_project_status_created_idx');
                });
            }
        }

        if (Schema::hasTable('projects')) {
            if ($this->indexExists('projects', 'projects_user_status_created_idx')) {
                Schema::table('projects', function (Blueprint $table) {
                    $table->dropIndex('projects_user_status_created_idx');
                });
            }
        }

        if (Schema::hasTable('health_checks')) {
            if ($this->indexExists('health_checks', 'health_checks_server_status_created_idx')) {
                Schema::table('health_checks', function (Blueprint $table) {
                    $table->dropIndex('health_checks_server_status_created_idx');
                });
            }
        }

        if (Schema::hasTable('audit_logs')) {
            if ($this->indexExists('audit_logs', 'audit_logs_user_action_created_idx')) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->dropIndex('audit_logs_user_action_created_idx');
                });
            }
        }
    }
};
