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
     * This migration adds critical performance indexes identified as missing from the schema.
     */
    public function up(): void
    {
        // Add composite index for projects table: status + updated_at
        // Common query: ->where('status', 'running')->orderBy('updated_at', 'desc')
        if (Schema::hasTable('projects') && ! $this->indexExists('projects', 'idx_status_updated_at')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->index(['status', 'updated_at'], 'idx_status_updated_at');
            });
        }

        // Add composite index for projects table: server_id + status
        // Common query: ->where('server_id', $id)->where('status', 'running')
        if (Schema::hasTable('projects') && ! $this->indexExists('projects', 'idx_server_status')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->index(['server_id', 'status'], 'idx_server_status');
            });
        }

        // Add composite index for projects table: user_id + team_id
        // Common query: ->where('user_id', $id)->orWhere('team_id', $teamId)
        if (Schema::hasTable('projects') &&
            Schema::hasColumn('projects', 'team_id') &&
            ! $this->indexExists('projects', 'idx_user_team')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->index(['user_id', 'team_id'], 'idx_user_team');
            });
        }

        // Add composite index for deployments table: project_id + created_at DESC
        // Common query: ->where('project_id', $id)->orderBy('created_at', 'desc')->limit(10)
        if (Schema::hasTable('deployments') && ! $this->indexExists('deployments', 'idx_project_created')) {
            Schema::table('deployments', function (Blueprint $table) {
                $table->index(['project_id', 'created_at'], 'idx_project_created');
            });
        }

        // Add composite index for deployments table: status + created_at
        // Common query: ->where('status', 'success')->orderBy('created_at', 'desc')
        if (Schema::hasTable('deployments') && ! $this->indexExists('deployments', 'idx_status_created')) {
            Schema::table('deployments', function (Blueprint $table) {
                $table->index(['status', 'created_at'], 'idx_status_created');
            });
        }

        // Add composite index for deployments table: user_id + status
        // Common query: ->where('user_id', $id)->where('status', 'running')
        if (Schema::hasTable('deployments') && ! $this->indexExists('deployments', 'idx_user_status')) {
            Schema::table('deployments', function (Blueprint $table) {
                $table->index(['user_id', 'status'], 'idx_user_status');
            });
        }

        // Add composite index for servers table: status + updated_at
        // Common query: ->where('status', 'online')->orderBy('updated_at', 'desc')
        if (Schema::hasTable('servers') && ! $this->indexExists('servers', 'idx_status_updated')) {
            Schema::table('servers', function (Blueprint $table) {
                $table->index(['status', 'updated_at'], 'idx_status_updated');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from projects table
        if (Schema::hasTable('projects')) {
            if ($this->indexExists('projects', 'idx_status_updated_at')) {
                Schema::table('projects', function (Blueprint $table) {
                    $table->dropIndex('idx_status_updated_at');
                });
            }
            if ($this->indexExists('projects', 'idx_server_status')) {
                Schema::table('projects', function (Blueprint $table) {
                    $table->dropIndex('idx_server_status');
                });
            }
            if ($this->indexExists('projects', 'idx_user_team')) {
                Schema::table('projects', function (Blueprint $table) {
                    $table->dropIndex('idx_user_team');
                });
            }
        }

        // Drop indexes from deployments table
        if (Schema::hasTable('deployments')) {
            if ($this->indexExists('deployments', 'idx_project_created')) {
                Schema::table('deployments', function (Blueprint $table) {
                    $table->dropIndex('idx_project_created');
                });
            }
            if ($this->indexExists('deployments', 'idx_status_created')) {
                Schema::table('deployments', function (Blueprint $table) {
                    $table->dropIndex('idx_status_created');
                });
            }
            if ($this->indexExists('deployments', 'idx_user_status')) {
                Schema::table('deployments', function (Blueprint $table) {
                    $table->dropIndex('idx_user_status');
                });
            }
        }

        // Drop indexes from servers table
        if (Schema::hasTable('servers')) {
            if ($this->indexExists('servers', 'idx_status_updated')) {
                Schema::table('servers', function (Blueprint $table) {
                    $table->dropIndex('idx_status_updated');
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
            $indexes = DB::select("PRAGMA index_list(`{$tableName}`)");
            foreach ($indexes as $index) {
                if ($index->name === $indexName) {
                    return true;
                }
            }
            return false;
        }

        if ($driver === 'pgsql') {
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
