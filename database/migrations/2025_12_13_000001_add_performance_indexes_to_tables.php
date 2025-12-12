<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        // Add indexes to audit_logs table
        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                if (!$this->indexExists('audit_logs', 'audit_logs_auditable_type_user_id_index')) {
                    $table->index(['auditable_type', 'user_id'], 'audit_logs_auditable_type_user_id_index');
                }
                if (!$this->indexExists('audit_logs', 'audit_logs_created_at_desc_index')) {
                    $table->index(['created_at'], 'audit_logs_created_at_desc_index');
                }
            });
        }

        // Add indexes to server_metrics table
        if (Schema::hasTable('server_metrics')) {
            Schema::table('server_metrics', function (Blueprint $table) {
                if (!$this->indexExists('server_metrics', 'server_metrics_server_id_recorded_at_index')) {
                    $table->index(['server_id', 'recorded_at'], 'server_metrics_server_id_recorded_at_index');
                }
                if (!$this->indexExists('server_metrics', 'server_metrics_recorded_at_index')) {
                    $table->index(['recorded_at'], 'server_metrics_recorded_at_index');
                }
            });
        }

        // Add indexes to deployments table
        if (Schema::hasTable('deployments')) {
            Schema::table('deployments', function (Blueprint $table) {
                if (!$this->indexExists('deployments', 'deployments_project_id_status_index')) {
                    $table->index(['project_id', 'status'], 'deployments_project_id_status_index');
                }
                if (!$this->indexExists('deployments', 'deployments_project_id_created_at_index')) {
                    $table->index(['project_id', 'created_at'], 'deployments_project_id_created_at_index');
                }
            });
        }

        // Add indexes to projects table
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                if (!$this->indexExists('projects', 'projects_status_index')) {
                    $table->index(['status'], 'projects_status_index');
                }
                if (!$this->indexExists('projects', 'projects_server_id_status_index')) {
                    $table->index(['server_id', 'status'], 'projects_server_id_status_index');
                }
            });
        }

        // Add indexes to domains table
        if (Schema::hasTable('domains')) {
            Schema::table('domains', function (Blueprint $table) {
                if (!$this->indexExists('domains', 'domains_ssl_expires_at_index')) {
                    $table->index(['ssl_expires_at'], 'domains_ssl_expires_at_index');
                }
                if (!$this->indexExists('domains', 'domains_ssl_enabled_index')) {
                    $table->index(['ssl_enabled'], 'domains_ssl_enabled_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                if ($this->indexExists('audit_logs', 'audit_logs_auditable_type_user_id_index')) {
                    $table->dropIndex('audit_logs_auditable_type_user_id_index');
                }
                if ($this->indexExists('audit_logs', 'audit_logs_created_at_desc_index')) {
                    $table->dropIndex('audit_logs_created_at_desc_index');
                }
            });
        }

        if (Schema::hasTable('server_metrics')) {
            Schema::table('server_metrics', function (Blueprint $table) {
                if ($this->indexExists('server_metrics', 'server_metrics_server_id_recorded_at_index')) {
                    $table->dropIndex('server_metrics_server_id_recorded_at_index');
                }
                if ($this->indexExists('server_metrics', 'server_metrics_recorded_at_index')) {
                    $table->dropIndex('server_metrics_recorded_at_index');
                }
            });
        }

        if (Schema::hasTable('deployments')) {
            Schema::table('deployments', function (Blueprint $table) {
                if ($this->indexExists('deployments', 'deployments_project_id_status_index')) {
                    $table->dropIndex('deployments_project_id_status_index');
                }
                if ($this->indexExists('deployments', 'deployments_project_id_created_at_index')) {
                    $table->dropIndex('deployments_project_id_created_at_index');
                }
            });
        }

        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                if ($this->indexExists('projects', 'projects_status_index')) {
                    $table->dropIndex('projects_status_index');
                }
                if ($this->indexExists('projects', 'projects_server_id_status_index')) {
                    $table->dropIndex('projects_server_id_status_index');
                }
            });
        }

        if (Schema::hasTable('domains')) {
            Schema::table('domains', function (Blueprint $table) {
                if ($this->indexExists('domains', 'domains_ssl_expires_at_index')) {
                    $table->dropIndex('domains_ssl_expires_at_index');
                }
                if ($this->indexExists('domains', 'domains_ssl_enabled_index')) {
                    $table->dropIndex('domains_ssl_enabled_index');
                }
            });
        }
    }
};
