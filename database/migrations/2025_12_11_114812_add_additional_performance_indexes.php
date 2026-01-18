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
     * This migration adds additional performance indexes for common query patterns
     * that are not covered by existing index migrations.
     */
    public function up(): void
    {
        // Add composite index for deployments filtering by status and ordering by created_at
        // Common query: ->where('status', 'success')->orderBy('created_at', 'desc')
        if (Schema::hasTable('deployments') && ! $this->indexExists('deployments', 'idx_deployments_status_created')) {
            Schema::table('deployments', function (Blueprint $table) {
                $table->index(['status', 'created_at'], 'idx_deployments_status_created');
            });
        }

        // Add index for deployments filtering by branch (for branch-specific deployments)
        // Common query: ->where('project_id', $id)->where('branch', 'main')
        if (Schema::hasTable('deployments') && ! $this->indexExists('deployments', 'idx_deployments_project_branch')) {
            Schema::table('deployments', function (Blueprint $table) {
                $table->index(['project_id', 'branch'], 'idx_deployments_project_branch');
            });
        }

        // Add index for deployments completed_at for statistics and reporting
        // Common query: ->whereNotNull('completed_at')->orderBy('completed_at', 'desc')
        if (Schema::hasTable('deployments') && ! $this->indexExists('deployments', 'idx_deployments_completed_at')) {
            Schema::table('deployments', function (Blueprint $table) {
                $table->index('completed_at', 'idx_deployments_completed_at');
            });
        }

        // Add composite index for projects filtering by framework and status
        // Common query: ->where('framework', 'laravel')->where('status', 'running')
        if (Schema::hasTable('projects') && ! $this->indexExists('projects', 'idx_projects_framework_status')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->index(['framework', 'status'], 'idx_projects_framework_status');
            });
        }

        // Add index for projects last_deployed_at for sorting recent deployments
        // Common query: ->orderBy('last_deployed_at', 'desc')
        if (Schema::hasTable('projects') && ! $this->indexExists('projects', 'idx_projects_last_deployed')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->index('last_deployed_at', 'idx_projects_last_deployed');
            });
        }

        // Add index for projects auto_deploy filtering
        // Common query: ->where('auto_deploy', true)->where('status', 'running')
        if (Schema::hasTable('projects') && ! $this->indexExists('projects', 'idx_projects_auto_deploy_status')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->index(['auto_deploy', 'status'], 'idx_projects_auto_deploy_status');
            });
        }

        // Add composite index for server_metrics time-series queries with recorded_at
        // Common query: ->where('server_id', $id)->orderBy('recorded_at', 'desc')->limit(100)
        if (Schema::hasTable('server_metrics') && ! $this->indexExists('server_metrics', 'idx_server_metrics_recorded')) {
            Schema::table('server_metrics', function (Blueprint $table) {
                $table->index('recorded_at', 'idx_server_metrics_recorded');
            });
        }

        // Add index for server_metrics filtering high CPU usage
        // Common query: ->where('cpu_usage', '>', 80)->orderBy('recorded_at', 'desc')
        if (Schema::hasTable('server_metrics') && ! $this->indexExists('server_metrics', 'idx_server_metrics_cpu_recorded')) {
            Schema::table('server_metrics', function (Blueprint $table) {
                $table->index(['cpu_usage', 'recorded_at'], 'idx_server_metrics_cpu_recorded');
            });
        }

        // Add index for server_metrics filtering high memory usage
        // Common query: ->where('memory_usage', '>', 80)->orderBy('recorded_at', 'desc')
        if (Schema::hasTable('server_metrics') && ! $this->indexExists('server_metrics', 'idx_server_metrics_memory_recorded')) {
            Schema::table('server_metrics', function (Blueprint $table) {
                $table->index(['memory_usage', 'recorded_at'], 'idx_server_metrics_memory_recorded');
            });
        }

        // Add index for server_metrics filtering high disk usage
        // Common query: ->where('disk_usage', '>', 80)->orderBy('recorded_at', 'desc')
        if (Schema::hasTable('server_metrics') && ! $this->indexExists('server_metrics', 'idx_server_metrics_disk_recorded')) {
            Schema::table('server_metrics', function (Blueprint $table) {
                $table->index(['disk_usage', 'recorded_at'], 'idx_server_metrics_disk_recorded');
            });
        }

        // Add index for servers filtering by status and user
        // Common query: ->where('user_id', $id)->where('status', 'online')
        if (Schema::hasTable('servers') && ! $this->indexExists('servers', 'idx_servers_user_status')) {
            Schema::table('servers', function (Blueprint $table) {
                $table->index(['user_id', 'status'], 'idx_servers_user_status');
            });
        }

        // Add index for domains filtering by status and SSL expiration
        // Common query: ->where('status', 'active')->whereNotNull('ssl_expires_at')
        if (Schema::hasTable('domains') && ! $this->indexExists('domains', 'idx_domains_status_ssl_expires')) {
            Schema::table('domains', function (Blueprint $table) {
                $table->index(['status', 'ssl_expires_at'], 'idx_domains_status_ssl_expires');
            });
        }

        // Add index for domains filtering by project and primary domain
        // Common query: ->where('project_id', $id)->where('is_primary', true)
        if (Schema::hasTable('domains') && ! $this->indexExists('domains', 'idx_domains_project_primary')) {
            Schema::table('domains', function (Blueprint $table) {
                $table->index(['project_id', 'is_primary'], 'idx_domains_project_primary');
            });
        }

        // Add index for health_checks filtering active checks by last_check_at
        // Common query: ->where('is_active', true)->orderBy('last_check_at', 'desc')
        if (Schema::hasTable('health_checks') && ! $this->indexExists('health_checks', 'idx_health_checks_active_last_check')) {
            Schema::table('health_checks', function (Blueprint $table) {
                $table->index(['is_active', 'last_check_at'], 'idx_health_checks_active_last_check');
            });
        }

        // Add index for backup_schedules next run time queries
        // Common query: ->where('is_active', true)->orderBy('next_run_at', 'asc')
        if (Schema::hasTable('backup_schedules') && ! $this->indexExists('backup_schedules', 'idx_backup_schedules_active_next_run')) {
            Schema::table('backup_schedules', function (Blueprint $table) {
                $table->index(['is_active', 'next_run_at'], 'idx_backup_schedules_active_next_run');
            });
        }

        // Add index for database_backups filtering by project and status
        // Common query: ->where('project_id', $id)->where('status', 'completed')
        if (Schema::hasTable('database_backups') && ! $this->indexExists('database_backups', 'idx_database_backups_project_status')) {
            Schema::table('database_backups', function (Blueprint $table) {
                $table->index(['project_id', 'status'], 'idx_database_backups_project_status');
            });
        }

        // Add index for scheduled_deployments filtering by scheduled_at
        // Common query: ->where('status', 'pending')->orderBy('scheduled_at', 'asc')
        if (Schema::hasTable('scheduled_deployments') && ! $this->indexExists('scheduled_deployments', 'idx_scheduled_deployments_status_scheduled')) {
            Schema::table('scheduled_deployments', function (Blueprint $table) {
                $table->index(['status', 'scheduled_at'], 'idx_scheduled_deployments_status_scheduled');
            });
        }

        // Add index for webhook_deliveries filtering by project and status
        // Common query: ->where('project_id', $id)->where('status', 'failed')
        if (Schema::hasTable('webhook_deliveries') && ! $this->indexExists('webhook_deliveries', 'idx_webhook_deliveries_project_status_created')) {
            Schema::table('webhook_deliveries', function (Blueprint $table) {
                $table->index(['project_id', 'status', 'created_at'], 'idx_webhook_deliveries_project_status_created');
            });
        }

        // Add index for audit_logs filtering by action type
        // Common query: ->where('action', 'deployment.created')->orderBy('created_at', 'desc')
        if (Schema::hasTable('audit_logs') && ! $this->indexExists('audit_logs', 'idx_audit_logs_action_created')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->index(['action', 'created_at'], 'idx_audit_logs_action_created');
            });
        }

        // Add index for team_members filtering by team and role
        // Common query: ->where('team_id', $id)->where('role', 'admin')
        if (Schema::hasTable('team_members') && ! $this->indexExists('team_members', 'idx_team_members_team_role')) {
            Schema::table('team_members', function (Blueprint $table) {
                $table->index(['team_id', 'role'], 'idx_team_members_team_role');
            });
        }

        // Add index for pipeline_runs filtering by project and status
        // Common query: ->where('project_id', $id)->where('status', 'running')
        if (Schema::hasTable('pipeline_runs') && ! $this->indexExists('pipeline_runs', 'idx_pipeline_runs_project_status')) {
            Schema::table('pipeline_runs', function (Blueprint $table) {
                $table->index(['project_id', 'status'], 'idx_pipeline_runs_project_status');
            });
        }

        // Add index for security_events filtering by server and event_type
        // Common query: ->where('server_id', $id)->where('event_type', 'failed_login')
        if (Schema::hasTable('security_events') && ! $this->indexExists('security_events', 'idx_security_events_server_type')) {
            Schema::table('security_events', function (Blueprint $table) {
                $table->index(['server_id', 'event_type'], 'idx_security_events_server_type');
            });
        }

        // Add index for log_entries filtering by level and source
        // Common query: ->where('level', 'error')->where('source', 'app')
        if (Schema::hasTable('log_entries') && ! $this->indexExists('log_entries', 'idx_log_entries_level_source_logged')) {
            Schema::table('log_entries', function (Blueprint $table) {
                $table->index(['level', 'source', 'logged_at'], 'idx_log_entries_level_source_logged');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from deployments table
        if (Schema::hasTable('deployments')) {
            if ($this->indexExists('deployments', 'idx_deployments_status_created')) {
                Schema::table('deployments', function (Blueprint $table) {
                    $table->dropIndex('idx_deployments_status_created');
                });
            }
            if ($this->indexExists('deployments', 'idx_deployments_project_branch')) {
                Schema::table('deployments', function (Blueprint $table) {
                    $table->dropIndex('idx_deployments_project_branch');
                });
            }
            if ($this->indexExists('deployments', 'idx_deployments_completed_at')) {
                Schema::table('deployments', function (Blueprint $table) {
                    $table->dropIndex('idx_deployments_completed_at');
                });
            }
        }

        // Drop indexes from projects table
        if (Schema::hasTable('projects')) {
            if ($this->indexExists('projects', 'idx_projects_framework_status')) {
                Schema::table('projects', function (Blueprint $table) {
                    $table->dropIndex('idx_projects_framework_status');
                });
            }
            if ($this->indexExists('projects', 'idx_projects_last_deployed')) {
                Schema::table('projects', function (Blueprint $table) {
                    $table->dropIndex('idx_projects_last_deployed');
                });
            }
            if ($this->indexExists('projects', 'idx_projects_auto_deploy_status')) {
                Schema::table('projects', function (Blueprint $table) {
                    $table->dropIndex('idx_projects_auto_deploy_status');
                });
            }
        }

        // Drop indexes from server_metrics table
        if (Schema::hasTable('server_metrics')) {
            if ($this->indexExists('server_metrics', 'idx_server_metrics_recorded')) {
                Schema::table('server_metrics', function (Blueprint $table) {
                    $table->dropIndex('idx_server_metrics_recorded');
                });
            }
            if ($this->indexExists('server_metrics', 'idx_server_metrics_cpu_recorded')) {
                Schema::table('server_metrics', function (Blueprint $table) {
                    $table->dropIndex('idx_server_metrics_cpu_recorded');
                });
            }
            if ($this->indexExists('server_metrics', 'idx_server_metrics_memory_recorded')) {
                Schema::table('server_metrics', function (Blueprint $table) {
                    $table->dropIndex('idx_server_metrics_memory_recorded');
                });
            }
            if ($this->indexExists('server_metrics', 'idx_server_metrics_disk_recorded')) {
                Schema::table('server_metrics', function (Blueprint $table) {
                    $table->dropIndex('idx_server_metrics_disk_recorded');
                });
            }
        }

        // Drop indexes from servers table
        if (Schema::hasTable('servers')) {
            if ($this->indexExists('servers', 'idx_servers_user_status')) {
                Schema::table('servers', function (Blueprint $table) {
                    $table->dropIndex('idx_servers_user_status');
                });
            }
        }

        // Drop indexes from domains table
        if (Schema::hasTable('domains')) {
            if ($this->indexExists('domains', 'idx_domains_status_ssl_expires')) {
                Schema::table('domains', function (Blueprint $table) {
                    $table->dropIndex('idx_domains_status_ssl_expires');
                });
            }
            if ($this->indexExists('domains', 'idx_domains_project_primary')) {
                Schema::table('domains', function (Blueprint $table) {
                    $table->dropIndex('idx_domains_project_primary');
                });
            }
        }

        // Drop indexes from health_checks table
        if (Schema::hasTable('health_checks')) {
            if ($this->indexExists('health_checks', 'idx_health_checks_active_last_check')) {
                Schema::table('health_checks', function (Blueprint $table) {
                    $table->dropIndex('idx_health_checks_active_last_check');
                });
            }
        }

        // Drop indexes from backup_schedules table
        if (Schema::hasTable('backup_schedules')) {
            if ($this->indexExists('backup_schedules', 'idx_backup_schedules_active_next_run')) {
                Schema::table('backup_schedules', function (Blueprint $table) {
                    $table->dropIndex('idx_backup_schedules_active_next_run');
                });
            }
        }

        // Drop indexes from database_backups table
        if (Schema::hasTable('database_backups')) {
            if ($this->indexExists('database_backups', 'idx_database_backups_project_status')) {
                Schema::table('database_backups', function (Blueprint $table) {
                    $table->dropIndex('idx_database_backups_project_status');
                });
            }
        }

        // Drop indexes from scheduled_deployments table
        if (Schema::hasTable('scheduled_deployments')) {
            if ($this->indexExists('scheduled_deployments', 'idx_scheduled_deployments_status_scheduled')) {
                Schema::table('scheduled_deployments', function (Blueprint $table) {
                    $table->dropIndex('idx_scheduled_deployments_status_scheduled');
                });
            }
        }

        // Drop indexes from webhook_deliveries table
        if (Schema::hasTable('webhook_deliveries')) {
            if ($this->indexExists('webhook_deliveries', 'idx_webhook_deliveries_project_status_created')) {
                Schema::table('webhook_deliveries', function (Blueprint $table) {
                    $table->dropIndex('idx_webhook_deliveries_project_status_created');
                });
            }
        }

        // Drop indexes from audit_logs table
        if (Schema::hasTable('audit_logs')) {
            if ($this->indexExists('audit_logs', 'idx_audit_logs_action_created')) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->dropIndex('idx_audit_logs_action_created');
                });
            }
        }

        // Drop indexes from team_members table
        if (Schema::hasTable('team_members')) {
            if ($this->indexExists('team_members', 'idx_team_members_team_role')) {
                Schema::table('team_members', function (Blueprint $table) {
                    $table->dropIndex('idx_team_members_team_role');
                });
            }
        }

        // Drop indexes from pipeline_runs table
        if (Schema::hasTable('pipeline_runs')) {
            if ($this->indexExists('pipeline_runs', 'idx_pipeline_runs_project_status')) {
                Schema::table('pipeline_runs', function (Blueprint $table) {
                    $table->dropIndex('idx_pipeline_runs_project_status');
                });
            }
        }

        // Drop indexes from security_events table
        if (Schema::hasTable('security_events')) {
            if ($this->indexExists('security_events', 'idx_security_events_server_type')) {
                Schema::table('security_events', function (Blueprint $table) {
                    $table->dropIndex('idx_security_events_server_type');
                });
            }
        }

        // Drop indexes from log_entries table
        if (Schema::hasTable('log_entries')) {
            if ($this->indexExists('log_entries', 'idx_log_entries_level_source_logged')) {
                Schema::table('log_entries', function (Blueprint $table) {
                    $table->dropIndex('idx_log_entries_level_source_logged');
                });
            }
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
