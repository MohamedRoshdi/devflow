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
     * Phase 7 Performance Optimization - Additional indexes for frequently queried columns
     */
    public function up(): void
    {
        // Add composite index for deployments (project_id, server_id, status, created_at)
        // This optimizes the common query pattern: filtering by project/server and status, ordered by date
        if (Schema::hasTable('deployments') && ! $this->indexExists('deployments', 'deployments_project_server_status_created_at_idx')) {
            Schema::table('deployments', function (Blueprint $table) {
                $table->index(['project_id', 'server_id', 'status', 'created_at'], 'deployments_project_server_status_created_at_idx');
            });
        }

        // Add composite index for server_metrics (server_id, created_at)
        // This is critical for time-series queries on dashboard and analytics
        if (Schema::hasTable('server_metrics') && ! $this->indexExists('server_metrics', 'server_metrics_server_created_idx')) {
            Schema::table('server_metrics', function (Blueprint $table) {
                $table->index(['server_id', 'created_at'], 'server_metrics_server_created_idx');
            });
        }

        // Add composite index for server_metrics for resource monitoring
        // Helps quickly find high resource usage scenarios
        if (Schema::hasTable('server_metrics') && ! $this->indexExists('server_metrics', 'server_metrics_resource_usage_idx')) {
            Schema::table('server_metrics', function (Blueprint $table) {
                $table->index(['cpu_usage', 'memory_usage', 'disk_usage'], 'server_metrics_resource_usage_idx');
            });
        }

        // Add composite index for health_checks (project_id, status)
        // Optimizes queries for checking project health status
        if (Schema::hasTable('health_checks') && ! $this->indexExists('health_checks', 'health_checks_project_status_idx')) {
            Schema::table('health_checks', function (Blueprint $table) {
                $table->index(['project_id', 'status'], 'health_checks_project_status_idx');
            });
        }

        // Add composite index for health_checks (server_id, status)
        // Optimizes queries for checking server health status
        if (Schema::hasTable('health_checks') && ! $this->indexExists('health_checks', 'health_checks_server_status_idx')) {
            Schema::table('health_checks', function (Blueprint $table) {
                $table->index(['server_id', 'status'], 'health_checks_server_status_idx');
            });
        }

        // Add composite index for health_checks for monitoring active checks
        if (Schema::hasTable('health_checks') && ! $this->indexExists('health_checks', 'health_checks_active_status_idx')) {
            Schema::table('health_checks', function (Blueprint $table) {
                $table->index(['is_active', 'status', 'last_failure_at'], 'health_checks_active_status_idx');
            });
        }

        // Add composite index for audit_logs (user_id, created_at)
        // Optimizes queries for user activity history
        if (Schema::hasTable('audit_logs') && ! $this->indexExists('audit_logs', 'audit_logs_user_created_idx')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->index(['user_id', 'created_at'], 'audit_logs_user_created_idx');
            });
        }

        // Add composite index for audit_logs (auditable_type, created_at)
        // Optimizes queries for model-specific audit trails
        if (Schema::hasTable('audit_logs') && ! $this->indexExists('audit_logs', 'audit_logs_type_created_idx')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->index(['auditable_type', 'created_at'], 'audit_logs_type_created_idx');
            });
        }

        // Add composite index for domains (project_id, ssl_enabled)
        // Optimizes queries for SSL certificate management
        if (Schema::hasTable('domains') && ! $this->indexExists('domains', 'domains_project_ssl_idx')) {
            Schema::table('domains', function (Blueprint $table) {
                $table->index(['project_id', 'ssl_enabled'], 'domains_project_ssl_idx');
            });
        }

        // Add composite index for domains for SSL expiration monitoring
        if (Schema::hasTable('domains') && ! $this->indexExists('domains', 'domains_ssl_expiration_idx')) {
            Schema::table('domains', function (Blueprint $table) {
                $table->index(['ssl_enabled', 'ssl_expires_at', 'auto_renew_ssl'], 'domains_ssl_expiration_idx');
            });
        }

        // Add index for projects ordered queries (commonly sorted by created_at/updated_at)
        if (Schema::hasTable('projects') && ! $this->indexExists('projects', 'projects_status_updated_idx')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->index(['status', 'updated_at'], 'projects_status_updated_idx');
            });
        }

        // Add index for servers health monitoring
        if (Schema::hasTable('servers') && ! $this->indexExists('servers', 'servers_status_ping_idx')) {
            Schema::table('servers', function (Blueprint $table) {
                $table->index(['status', 'last_ping_at'], 'servers_status_ping_idx');
            });
        }

        // Add index for deployment statistics and filtering
        if (Schema::hasTable('deployments') && ! $this->indexExists('deployments', 'deployments_status_created_idx')) {
            Schema::table('deployments', function (Blueprint $table) {
                $table->index(['status', 'created_at'], 'deployments_status_created_idx');
            });
        }

        // Add index for SSL certificates expiration monitoring
        if (Schema::hasTable('ssl_certificates') && ! $this->indexExists('ssl_certificates', 'ssl_certificates_expires_status_idx')) {
            Schema::table('ssl_certificates', function (Blueprint $table) {
                $table->index(['expires_at', 'status'], 'ssl_certificates_expires_status_idx');
            });
        }

        // Add index for notification logs if table exists
        if (Schema::hasTable('notification_logs') && ! $this->indexExists('notification_logs', 'notification_logs_created_status_idx')) {
            Schema::table('notification_logs', function (Blueprint $table) {
                $table->index(['created_at', 'status'], 'notification_logs_created_status_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from deployments
        if (Schema::hasTable('deployments') && $this->indexExists('deployments', 'deployments_project_server_status_created_at_idx')) {
            Schema::table('deployments', function (Blueprint $table) {
                $table->dropIndex('deployments_project_server_status_created_at_idx');
            });
        }

        if (Schema::hasTable('deployments') && $this->indexExists('deployments', 'deployments_status_created_idx')) {
            Schema::table('deployments', function (Blueprint $table) {
                $table->dropIndex('deployments_status_created_idx');
            });
        }

        // Drop indexes from server_metrics
        if (Schema::hasTable('server_metrics') && $this->indexExists('server_metrics', 'server_metrics_server_created_idx')) {
            Schema::table('server_metrics', function (Blueprint $table) {
                $table->dropIndex('server_metrics_server_created_idx');
            });
        }

        if (Schema::hasTable('server_metrics') && $this->indexExists('server_metrics', 'server_metrics_resource_usage_idx')) {
            Schema::table('server_metrics', function (Blueprint $table) {
                $table->dropIndex('server_metrics_resource_usage_idx');
            });
        }

        // Drop indexes from health_checks
        if (Schema::hasTable('health_checks')) {
            if ($this->indexExists('health_checks', 'health_checks_project_status_idx')) {
                Schema::table('health_checks', function (Blueprint $table) {
                    $table->dropIndex('health_checks_project_status_idx');
                });
            }

            if ($this->indexExists('health_checks', 'health_checks_server_status_idx')) {
                Schema::table('health_checks', function (Blueprint $table) {
                    $table->dropIndex('health_checks_server_status_idx');
                });
            }

            if ($this->indexExists('health_checks', 'health_checks_active_status_idx')) {
                Schema::table('health_checks', function (Blueprint $table) {
                    $table->dropIndex('health_checks_active_status_idx');
                });
            }
        }

        // Drop indexes from audit_logs
        if (Schema::hasTable('audit_logs') && $this->indexExists('audit_logs', 'audit_logs_user_created_idx')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropIndex('audit_logs_user_created_idx');
            });
        }

        if (Schema::hasTable('audit_logs') && $this->indexExists('audit_logs', 'audit_logs_type_created_idx')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropIndex('audit_logs_type_created_idx');
            });
        }

        // Drop indexes from domains
        if (Schema::hasTable('domains') && $this->indexExists('domains', 'domains_project_ssl_idx')) {
            Schema::table('domains', function (Blueprint $table) {
                $table->dropIndex('domains_project_ssl_idx');
            });
        }

        if (Schema::hasTable('domains') && $this->indexExists('domains', 'domains_ssl_expiration_idx')) {
            Schema::table('domains', function (Blueprint $table) {
                $table->dropIndex('domains_ssl_expiration_idx');
            });
        }

        // Drop indexes from projects
        if (Schema::hasTable('projects') && $this->indexExists('projects', 'projects_status_updated_idx')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropIndex('projects_status_updated_idx');
            });
        }

        // Drop indexes from servers
        if (Schema::hasTable('servers') && $this->indexExists('servers', 'servers_status_ping_idx')) {
            Schema::table('servers', function (Blueprint $table) {
                $table->dropIndex('servers_status_ping_idx');
            });
        }

        // Drop indexes from ssl_certificates
        if (Schema::hasTable('ssl_certificates') && $this->indexExists('ssl_certificates', 'ssl_certificates_expires_status_idx')) {
            Schema::table('ssl_certificates', function (Blueprint $table) {
                $table->dropIndex('ssl_certificates_expires_status_idx');
            });
        }

        // Drop indexes from notification_logs
        if (Schema::hasTable('notification_logs') && $this->indexExists('notification_logs', 'notification_logs_created_status_idx')) {
            Schema::table('notification_logs', function (Blueprint $table) {
                $table->dropIndex('notification_logs_created_status_idx');
            });
        }
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $tableName, string $indexName): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite uses PRAGMA index_list
            $indexes = DB::select("PRAGMA index_list(`{$tableName}`)");
            foreach ($indexes as $index) {
                if ($index->name === $indexName) {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'pgsql') {
            // PostgreSQL uses pg_indexes
            $query = 'SELECT COUNT(*) as count
                      FROM pg_indexes
                      WHERE tablename = ?
                      AND indexname = ?';

            $result = DB::select($query, [$tableName, $indexName]);

            return $result[0]->count > 0;
        }

        // MySQL/MariaDB
        $databaseName = Schema::getConnection()->getDatabaseName();
        $query = 'SELECT COUNT(*) as count
                  FROM information_schema.statistics
                  WHERE table_schema = ?
                  AND table_name = ?
                  AND index_name = ?';

        $result = DB::select($query, [$databaseName, $tableName, $indexName]);

        return $result[0]->count > 0;
    }
};
