<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * These composite indexes are designed to optimize the most common queries
     * identified during performance analysis.
     */
    public function up(): void
    {
        // Deployments table - frequently queried by project + status + date
        Schema::table('deployments', function (Blueprint $table) {
            // For filtering deployments by project and status (DeploymentList)
            if (!$this->indexExists('deployments', 'idx_deployments_project_status_created')) {
                $table->index(['project_id', 'status', 'created_at'], 'idx_deployments_project_status_created');
            }

            // For finding running deployments per project
            if (!$this->indexExists('deployments', 'idx_deployments_project_running')) {
                $table->index(['project_id', 'status'], 'idx_deployments_project_running');
            }

            // For deployment timeline queries
            if (!$this->indexExists('deployments', 'idx_deployments_user_created')) {
                $table->index(['user_id', 'created_at'], 'idx_deployments_user_created');
            }
        });

        // Server metrics table - time-series queries are critical
        Schema::table('server_metrics', function (Blueprint $table) {
            // For fetching metrics by server within a time range
            if (!$this->indexExists('server_metrics', 'idx_server_metrics_server_time')) {
                $table->index(['server_id', 'created_at'], 'idx_server_metrics_server_time');
            }

            // For aggregation queries (latest metrics per server)
            if (!$this->indexExists('server_metrics', 'idx_server_metrics_created_server')) {
                $table->index(['created_at', 'server_id'], 'idx_server_metrics_created_server');
            }
        });

        // Projects table - common filters in project list
        Schema::table('projects', function (Blueprint $table) {
            // For team + status filtering (common dashboard query)
            if (!$this->indexExists('projects', 'idx_projects_team_status')) {
                $table->index(['team_id', 'status'], 'idx_projects_team_status');
            }

            // For server-based project listing
            if (!$this->indexExists('projects', 'idx_projects_server_status')) {
                $table->index(['server_id', 'status'], 'idx_projects_server_status');
            }
        });

        // Domains table - SSL expiry checks
        Schema::table('domains', function (Blueprint $table) {
            // For SSL certificate expiry monitoring
            if (!$this->indexExists('domains', 'idx_domains_ssl_expiry')) {
                $table->index(['ssl_enabled', 'ssl_expires_at'], 'idx_domains_ssl_expiry');
            }

            // For project domain lookups
            if (!$this->indexExists('domains', 'idx_domains_project_primary')) {
                $table->index(['project_id', 'is_primary'], 'idx_domains_project_primary');
            }
        });

        // Health checks - monitoring queries
        Schema::table('health_checks', function (Blueprint $table) {
            // For active health checks with status
            if (!$this->indexExists('health_checks', 'idx_health_checks_active_status')) {
                $table->index(['is_active', 'last_check_status'], 'idx_health_checks_active_status');
            }
        });

        // Health check results - time-series monitoring
        Schema::table('health_check_results', function (Blueprint $table) {
            // For fetching results by check within time range
            if (!$this->indexExists('health_check_results', 'idx_health_results_check_time')) {
                $table->index(['health_check_id', 'created_at'], 'idx_health_results_check_time');
            }
        });

        // Audit logs - admin queries
        Schema::table('audit_logs', function (Blueprint $table) {
            // For filtering audit logs by user and time
            if (!$this->indexExists('audit_logs', 'idx_audit_user_created')) {
                $table->index(['user_id', 'created_at'], 'idx_audit_user_created');
            }

            // For filtering by action type
            if (!$this->indexExists('audit_logs', 'idx_audit_action_created')) {
                $table->index(['action', 'created_at'], 'idx_audit_action_created');
            }
        });

        // Webhook deliveries - webhook monitoring
        Schema::table('webhook_deliveries', function (Blueprint $table) {
            // For project webhook history
            if (!$this->indexExists('webhook_deliveries', 'idx_webhook_project_created')) {
                $table->index(['project_id', 'created_at'], 'idx_webhook_project_created');
            }

            // For finding failed deliveries
            if (!$this->indexExists('webhook_deliveries', 'idx_webhook_status_created')) {
                $table->index(['status', 'created_at'], 'idx_webhook_status_created');
            }
        });

        // Security incidents - security monitoring
        if (Schema::hasTable('security_incidents')) {
            Schema::table('security_incidents', function (Blueprint $table) {
                // For server incident history
                if (!$this->indexExists('security_incidents', 'idx_incidents_server_status')) {
                    $table->index(['server_id', 'status'], 'idx_incidents_server_status');
                }

                // For severity-based monitoring
                if (!$this->indexExists('security_incidents', 'idx_incidents_severity_status')) {
                    $table->index(['severity', 'status', 'detected_at'], 'idx_incidents_severity_status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deployments', function (Blueprint $table) {
            $table->dropIndex('idx_deployments_project_status_created');
            $table->dropIndex('idx_deployments_project_running');
            $table->dropIndex('idx_deployments_user_created');
        });

        Schema::table('server_metrics', function (Blueprint $table) {
            $table->dropIndex('idx_server_metrics_server_time');
            $table->dropIndex('idx_server_metrics_created_server');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('idx_projects_team_status');
            $table->dropIndex('idx_projects_server_status');
        });

        Schema::table('domains', function (Blueprint $table) {
            $table->dropIndex('idx_domains_ssl_expiry');
            $table->dropIndex('idx_domains_project_primary');
        });

        Schema::table('health_checks', function (Blueprint $table) {
            $table->dropIndex('idx_health_checks_active_status');
        });

        Schema::table('health_check_results', function (Blueprint $table) {
            $table->dropIndex('idx_health_results_check_time');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_user_created');
            $table->dropIndex('idx_audit_action_created');
        });

        Schema::table('webhook_deliveries', function (Blueprint $table) {
            $table->dropIndex('idx_webhook_project_created');
            $table->dropIndex('idx_webhook_status_created');
        });

        if (Schema::hasTable('security_incidents')) {
            Schema::table('security_incidents', function (Blueprint $table) {
                $table->dropIndex('idx_incidents_server_status');
                $table->dropIndex('idx_incidents_severity_status');
            });
        }
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = $connection->select("PRAGMA index_list({$table})");
            foreach ($indexes as $index) {
                if ($index->name === $indexName) {
                    return true;
                }
            }
            return false;
        }

        if ($driver === 'mysql') {
            $indexes = $connection->select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            return count($indexes) > 0;
        }

        if ($driver === 'pgsql') {
            $indexes = $connection->select(
                "SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?",
                [$table, $indexName]
            );
            return count($indexes) > 0;
        }

        return false;
    }
};
