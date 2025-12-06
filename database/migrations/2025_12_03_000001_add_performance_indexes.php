<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add missing indexes to projects table
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                // Individual column indexes
                if (! $this->indexExists('projects', 'projects_status_index')) {
                    $table->index('status', 'projects_status_index');
                }

                if (! $this->indexExists('projects', 'projects_framework_index')) {
                    $table->index('framework', 'projects_framework_index');
                }

                if (! $this->indexExists('projects', 'projects_last_deployed_at_index')) {
                    $table->index('last_deployed_at', 'projects_last_deployed_at_index');
                }

                if (! $this->indexExists('projects', 'projects_auto_deploy_index')) {
                    $table->index('auto_deploy', 'projects_auto_deploy_index');
                }

                // Composite indexes for common query patterns
                if (! $this->indexExists('projects', 'projects_status_framework_index')) {
                    $table->index(['status', 'framework'], 'projects_status_framework_index');
                }

                if (! $this->indexExists('projects', 'projects_user_id_created_at_index')) {
                    $table->index(['user_id', 'created_at'], 'projects_user_id_created_at_index');
                }
            });
        }

        // Add missing indexes to deployments table
        if (Schema::hasTable('deployments')) {
            Schema::table('deployments', function (Blueprint $table) {
                // Individual column indexes
                if (! $this->indexExists('deployments', 'deployments_status_index')) {
                    $table->index('status', 'deployments_status_index');
                }

                if (! $this->indexExists('deployments', 'deployments_server_id_index')) {
                    $table->index('server_id', 'deployments_server_id_index');
                }

                if (! $this->indexExists('deployments', 'deployments_commit_hash_index')) {
                    $table->index('commit_hash', 'deployments_commit_hash_index');
                }

                if (! $this->indexExists('deployments', 'deployments_triggered_by_index')) {
                    $table->index('triggered_by', 'deployments_triggered_by_index');
                }

                if (! $this->indexExists('deployments', 'deployments_started_at_index')) {
                    $table->index('started_at', 'deployments_started_at_index');
                }

                if (! $this->indexExists('deployments', 'deployments_completed_at_index')) {
                    $table->index('completed_at', 'deployments_completed_at_index');
                }

                // Composite indexes for common query patterns
                if (! $this->indexExists('deployments', 'deployments_project_id_status_started_at_index')) {
                    $table->index(['project_id', 'status', 'started_at'], 'deployments_project_id_status_started_at_index');
                }

                if (! $this->indexExists('deployments', 'deployments_server_id_status_index')) {
                    $table->index(['server_id', 'status'], 'deployments_server_id_status_index');
                }

                if (! $this->indexExists('deployments', 'deployments_user_id_created_at_index')) {
                    $table->index(['user_id', 'created_at'], 'deployments_user_id_created_at_index');
                }
            });
        }

        // Add missing indexes to servers table
        if (Schema::hasTable('servers')) {
            Schema::table('servers', function (Blueprint $table) {
                // Individual column indexes
                if (! $this->indexExists('servers', 'servers_status_index')) {
                    $table->index('status', 'servers_status_index');
                }

                if (! $this->indexExists('servers', 'servers_hostname_index')) {
                    $table->index('hostname', 'servers_hostname_index');
                }

                if (! $this->indexExists('servers', 'servers_ip_address_index')) {
                    $table->index('ip_address', 'servers_ip_address_index');
                }

                if (! $this->indexExists('servers', 'servers_docker_installed_index')) {
                    $table->index('docker_installed', 'servers_docker_installed_index');
                }

                if (! $this->indexExists('servers', 'servers_last_ping_at_index')) {
                    $table->index('last_ping_at', 'servers_last_ping_at_index');
                }

                // Composite indexes for common query patterns
                if (! $this->indexExists('servers', 'servers_status_docker_installed_index')) {
                    $table->index(['status', 'docker_installed'], 'servers_status_docker_installed_index');
                }

                if (! $this->indexExists('servers', 'servers_user_id_created_at_index')) {
                    $table->index(['user_id', 'created_at'], 'servers_user_id_created_at_index');
                }
            });
        }

        // Add missing indexes to domains table
        if (Schema::hasTable('domains')) {
            Schema::table('domains', function (Blueprint $table) {
                // Individual column indexes
                if (! $this->indexExists('domains', 'domains_status_index')) {
                    $table->index('status', 'domains_status_index');
                }

                if (! $this->indexExists('domains', 'domains_ssl_enabled_index')) {
                    $table->index('ssl_enabled', 'domains_ssl_enabled_index');
                }

                if (! $this->indexExists('domains', 'domains_dns_configured_index')) {
                    $table->index('dns_configured', 'domains_dns_configured_index');
                }

                if (! $this->indexExists('domains', 'domains_auto_renew_ssl_index')) {
                    $table->index('auto_renew_ssl', 'domains_auto_renew_ssl_index');
                }

                if (! $this->indexExists('domains', 'domains_ssl_issued_at_index')) {
                    $table->index('ssl_issued_at', 'domains_ssl_issued_at_index');
                }

                // Composite indexes for common query patterns
                if (! $this->indexExists('domains', 'domains_project_id_status_index')) {
                    $table->index(['project_id', 'status'], 'domains_project_id_status_index');
                }

                if (! $this->indexExists('domains', 'domains_ssl_enabled_ssl_expires_at_index')) {
                    $table->index(['ssl_enabled', 'ssl_expires_at'], 'domains_ssl_enabled_ssl_expires_at_index');
                }
            });
        }

        // Add missing indexes to server_metrics table
        if (Schema::hasTable('server_metrics')) {
            Schema::table('server_metrics', function (Blueprint $table) {
                // Individual column indexes
                if (! $this->indexExists('server_metrics', 'server_metrics_cpu_usage_index')) {
                    $table->index('cpu_usage', 'server_metrics_cpu_usage_index');
                }

                if (! $this->indexExists('server_metrics', 'server_metrics_memory_usage_index')) {
                    $table->index('memory_usage', 'server_metrics_memory_usage_index');
                }

                if (! $this->indexExists('server_metrics', 'server_metrics_disk_usage_index')) {
                    $table->index('disk_usage', 'server_metrics_disk_usage_index');
                }

                if (! $this->indexExists('server_metrics', 'server_metrics_created_at_index')) {
                    $table->index('created_at', 'server_metrics_created_at_index');
                }

                // Composite indexes for time-series queries
                if (! $this->indexExists('server_metrics', 'server_metrics_server_id_created_at_index')) {
                    $table->index(['server_id', 'created_at'], 'server_metrics_server_id_created_at_index');
                }
            });
        }

        // Add missing indexes to health_checks table (if exists)
        if (Schema::hasTable('health_checks')) {
            Schema::table('health_checks', function (Blueprint $table) {
                // Individual column indexes
                if (! $this->indexExists('health_checks', 'health_checks_check_type_index')) {
                    $table->index('check_type', 'health_checks_check_type_index');
                }

                if (! $this->indexExists('health_checks', 'health_checks_last_success_at_index')) {
                    $table->index('last_success_at', 'health_checks_last_success_at_index');
                }

                if (! $this->indexExists('health_checks', 'health_checks_last_failure_at_index')) {
                    $table->index('last_failure_at', 'health_checks_last_failure_at_index');
                }

                if (! $this->indexExists('health_checks', 'health_checks_consecutive_failures_index')) {
                    $table->index('consecutive_failures', 'health_checks_consecutive_failures_index');
                }

                // Composite indexes for common query patterns
                if (! $this->indexExists('health_checks', 'health_checks_project_id_status_index')) {
                    $table->index(['project_id', 'status'], 'health_checks_project_id_status_index');
                }

                if (! $this->indexExists('health_checks', 'health_checks_server_id_status_index')) {
                    $table->index(['server_id', 'status'], 'health_checks_server_id_status_index');
                }

                if (! $this->indexExists('health_checks', 'health_checks_status_consecutive_failures_index')) {
                    $table->index(['status', 'consecutive_failures'], 'health_checks_status_consecutive_failures_index');
                }
            });
        }

        // Add missing indexes to ssl_certificates table (if exists)
        if (Schema::hasTable('ssl_certificates')) {
            Schema::table('ssl_certificates', function (Blueprint $table) {
                // Individual column indexes
                if (! $this->indexExists('ssl_certificates', 'ssl_certificates_status_index')) {
                    $table->index('status', 'ssl_certificates_status_index');
                }

                if (! $this->indexExists('ssl_certificates', 'ssl_certificates_provider_index')) {
                    $table->index('provider', 'ssl_certificates_provider_index');
                }

                if (! $this->indexExists('ssl_certificates', 'ssl_certificates_domain_id_index')) {
                    $table->index('domain_id', 'ssl_certificates_domain_id_index');
                }

                if (! $this->indexExists('ssl_certificates', 'ssl_certificates_issued_at_index')) {
                    $table->index('issued_at', 'ssl_certificates_issued_at_index');
                }

                if (! $this->indexExists('ssl_certificates', 'ssl_certificates_last_renewal_attempt_index')) {
                    $table->index('last_renewal_attempt', 'ssl_certificates_last_renewal_attempt_index');
                }

                // Composite indexes for common query patterns
                if (! $this->indexExists('ssl_certificates', 'ssl_certificates_server_id_status_index')) {
                    $table->index(['server_id', 'status'], 'ssl_certificates_server_id_status_index');
                }

                if (! $this->indexExists('ssl_certificates', 'ssl_certificates_auto_renew_expires_at_index')) {
                    $table->index(['auto_renew', 'expires_at'], 'ssl_certificates_auto_renew_expires_at_index');
                }

                if (! $this->indexExists('ssl_certificates', 'ssl_certificates_status_provider_index')) {
                    $table->index(['status', 'provider'], 'ssl_certificates_status_provider_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from projects table
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('projects_status_index');
            $table->dropIndex('projects_framework_index');
            $table->dropIndex('projects_last_deployed_at_index');
            $table->dropIndex('projects_auto_deploy_index');
            $table->dropIndex('projects_status_framework_index');
            $table->dropIndex('projects_user_id_created_at_index');
        });

        // Drop indexes from deployments table
        Schema::table('deployments', function (Blueprint $table) {
            $table->dropIndex('deployments_status_index');
            $table->dropIndex('deployments_server_id_index');
            $table->dropIndex('deployments_commit_hash_index');
            $table->dropIndex('deployments_triggered_by_index');
            $table->dropIndex('deployments_started_at_index');
            $table->dropIndex('deployments_completed_at_index');
            $table->dropIndex('deployments_project_id_status_started_at_index');
            $table->dropIndex('deployments_server_id_status_index');
            $table->dropIndex('deployments_user_id_created_at_index');
        });

        // Drop indexes from servers table
        Schema::table('servers', function (Blueprint $table) {
            $table->dropIndex('servers_status_index');
            $table->dropIndex('servers_hostname_index');
            $table->dropIndex('servers_ip_address_index');
            $table->dropIndex('servers_docker_installed_index');
            $table->dropIndex('servers_last_ping_at_index');
            $table->dropIndex('servers_status_docker_installed_index');
            $table->dropIndex('servers_user_id_created_at_index');
        });

        // Drop indexes from domains table
        Schema::table('domains', function (Blueprint $table) {
            $table->dropIndex('domains_status_index');
            $table->dropIndex('domains_ssl_enabled_index');
            $table->dropIndex('domains_dns_configured_index');
            $table->dropIndex('domains_auto_renew_ssl_index');
            $table->dropIndex('domains_ssl_issued_at_index');
            $table->dropIndex('domains_project_id_status_index');
            $table->dropIndex('domains_ssl_enabled_ssl_expires_at_index');
        });

        // Drop indexes from server_metrics table
        Schema::table('server_metrics', function (Blueprint $table) {
            $table->dropIndex('server_metrics_cpu_usage_index');
            $table->dropIndex('server_metrics_memory_usage_index');
            $table->dropIndex('server_metrics_disk_usage_index');
            $table->dropIndex('server_metrics_created_at_index');
            $table->dropIndex('server_metrics_server_id_created_at_index');
        });

        // Drop indexes from health_checks table (if exists)
        if (Schema::hasTable('health_checks')) {
            Schema::table('health_checks', function (Blueprint $table) {
                $table->dropIndex('health_checks_check_type_index');
                $table->dropIndex('health_checks_last_success_at_index');
                $table->dropIndex('health_checks_last_failure_at_index');
                $table->dropIndex('health_checks_consecutive_failures_index');
                $table->dropIndex('health_checks_project_id_status_index');
                $table->dropIndex('health_checks_server_id_status_index');
                $table->dropIndex('health_checks_status_consecutive_failures_index');
            });
        }

        // Drop indexes from ssl_certificates table (if exists)
        if (Schema::hasTable('ssl_certificates')) {
            Schema::table('ssl_certificates', function (Blueprint $table) {
                $table->dropIndex('ssl_certificates_status_index');
                $table->dropIndex('ssl_certificates_provider_index');
                $table->dropIndex('ssl_certificates_domain_id_index');
                $table->dropIndex('ssl_certificates_issued_at_index');
                $table->dropIndex('ssl_certificates_last_renewal_attempt_index');
                $table->dropIndex('ssl_certificates_server_id_status_index');
                $table->dropIndex('ssl_certificates_auto_renew_expires_at_index');
                $table->dropIndex('ssl_certificates_status_provider_index');
            });
        }
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $tableName, string $indexName): bool
    {
        $connection = config('database.default');

        if ($connection === 'sqlite') {
            // SQLite uses PRAGMA index_list
            $indexes = DB::select("PRAGMA index_list(`{$tableName}`)");
            foreach ($indexes as $index) {
                if ($index->name === $indexName) {
                    return true;
                }
            }

            return false;
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
