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
     * This migration adds performance indexes to optimize common queries.
     */
    public function up(): void
    {
        // Add composite index on audit_logs for common filtering queries
        // The table already has individual indexes, but composite indexes improve performance
        // for queries that filter by multiple columns
        Schema::table('audit_logs', function (Blueprint $table) {
            // Check if index doesn't exist before adding
            // This index optimizes queries filtering by auditable_type and user_id together
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('audit_logs');

            if (!isset($indexes['audit_logs_auditable_type_user_id_index'])) {
                $table->index(['auditable_type', 'user_id'], 'audit_logs_auditable_type_user_id_index');
            }

            // This index optimizes queries ordering by created_at DESC (common for audit logs)
            if (!isset($indexes['audit_logs_created_at_desc_index'])) {
                $table->index(['created_at'], 'audit_logs_created_at_desc_index');
            }
        });

        // Ensure server_metrics has the composite index for efficient querying
        // This table is queried frequently for metrics in specific time ranges
        Schema::table('server_metrics', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('server_metrics');

            // The composite index (server_id, recorded_at) should already exist
            // but we add it if missing for optimal query performance
            if (!isset($indexes['server_metrics_server_id_recorded_at_index'])) {
                $table->index(['server_id', 'recorded_at'], 'server_metrics_server_id_recorded_at_index');
            }

            // Add index for time-based queries without server filtering
            if (!isset($indexes['server_metrics_recorded_at_index'])) {
                $table->index(['recorded_at'], 'server_metrics_recorded_at_index');
            }
        });

        // Add indexes to deployments table for common query patterns
        if (Schema::hasTable('deployments')) {
            Schema::table('deployments', function (Blueprint $table) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes('deployments');

                // Optimize queries filtering by project and status
                if (!isset($indexes['deployments_project_id_status_index'])) {
                    $table->index(['project_id', 'status'], 'deployments_project_id_status_index');
                }

                // Optimize queries filtering by project and created_at (for latest deployment)
                if (!isset($indexes['deployments_project_id_created_at_index'])) {
                    $table->index(['project_id', 'created_at'], 'deployments_project_id_created_at_index');
                }
            });
        }

        // Add indexes to projects table for common filtering
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes('projects');

                // Optimize queries filtering by status
                if (!isset($indexes['projects_status_index'])) {
                    $table->index(['status'], 'projects_status_index');
                }

                // Optimize queries filtering by server_id and status
                if (!isset($indexes['projects_server_id_status_index'])) {
                    $table->index(['server_id', 'status'], 'projects_server_id_status_index');
                }
            });
        }

        // Add indexes to domains table for SSL certificate monitoring
        if (Schema::hasTable('domains')) {
            Schema::table('domains', function (Blueprint $table) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes('domains');

                // Optimize queries checking SSL expiration
                if (!isset($indexes['domains_ssl_expires_at_index'])) {
                    $table->index(['ssl_expires_at'], 'domains_ssl_expires_at_index');
                }

                // Optimize queries filtering by SSL enabled status
                if (!isset($indexes['domains_ssl_enabled_index'])) {
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
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_auditable_type_user_id_index');
            $table->dropIndex('audit_logs_created_at_desc_index');
        });

        Schema::table('server_metrics', function (Blueprint $table) {
            $table->dropIndex('server_metrics_server_id_recorded_at_index');
            $table->dropIndex('server_metrics_recorded_at_index');
        });

        if (Schema::hasTable('deployments')) {
            Schema::table('deployments', function (Blueprint $table) {
                $table->dropIndex('deployments_project_id_status_index');
                $table->dropIndex('deployments_project_id_created_at_index');
            });
        }

        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropIndex('projects_status_index');
                $table->dropIndex('projects_server_id_status_index');
            });
        }

        if (Schema::hasTable('domains')) {
            Schema::table('domains', function (Blueprint $table) {
                $table->dropIndex('domains_ssl_expires_at_index');
                $table->dropIndex('domains_ssl_enabled_index');
            });
        }
    }
};
