<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\{DB, Artisan};

/**
 * Optimized MySQL database refresh trait using single transaction per test
 *
 * Key optimizations:
 * - Runs migrations once globally (not per test)
 * - Uses database transactions for test isolation (fast rollback)
 * - Properly handles nested transaction with proper cleanup
 * - Connection pooling and reuse
 */
trait RefreshMySQLDatabase
{
    /**
     * Track if database has been migrated globally
     */
    protected static bool $databaseMigrated = false;

    /**
     * Database connection name
     */
    protected string $mysqlConnection = 'mysql';

    /**
     * Track if current test has an active transaction
     */
    protected bool $hasActiveTransaction = false;

    /**
     * Setup MySQL database - run migrations once, start transaction
     */
    protected function setUpMySQLDatabase(): void
    {
        // Run migrations only once globally
        if (! static::$databaseMigrated) {
            $this->setupMySQLDatabaseOnce();
        }

        // Start fresh transaction for this test
        DB::connection($this->mysqlConnection)->beginTransaction();
        $this->hasActiveTransaction = true;
    }

    /**
     * One-time database setup verification
     *
     * NOTE: This trait assumes migrations have been run BEFORE tests start.
     * Run: ./setup-test-database.sh before executing tests
     */
    protected function setupMySQLDatabaseOnce(): void
    {
        $connection = DB::connection($this->mysqlConnection);

        // Verify migrations table exists
        $database = $connection->getDatabaseName();
        $migrationsTableExists = $connection
            ->select("SELECT COUNT(*) as count FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA = ?
                     AND TABLE_NAME = 'migrations'", [$database])[0]->count ?? 0;

        if ($migrationsTableExists == 0) {
            throw new \RuntimeException(
                "Migrations table does not exist! Please run './setup-test-database.sh' before running tests."
            );
        }

        // Verify migrations have been run
        $appliedMigrations = $connection->table('migrations')->count();
        if ($appliedMigrations < 10) {
            throw new \RuntimeException(
                "Test database is not properly migrated ($appliedMigrations migrations found). " .
                "Please run './setup-test-database.sh' before running tests."
            );
        }

        static::$databaseMigrated = true;
    }

    /**
     * Cleanup after test - rollback transaction
     */
    protected function tearDownMySQLDatabase(): void
    {
        if (! $this->hasActiveTransaction) {
            return;
        }

        try {
            $connection = DB::connection($this->mysqlConnection);

            // Rollback all nested transactions
            while ($connection->transactionLevel() > 0) {
                $connection->rollBack();
            }
        } catch (\Exception $e) {
            // Ignore rollback errors (connection might be closed)
            // Try to reconnect and rollback
            try {
                $connection = DB::reconnect($this->mysqlConnection);
                while ($connection->transactionLevel() > 0) {
                    $connection->rollBack();
                }
            } catch (\Exception $e2) {
                // Final fallback: disconnect completely
                try {
                    DB::connection($this->mysqlConnection)->disconnect();
                } catch (\Exception $e3) {
                    // Ignore
                }
            }
        } finally {
            $this->hasActiveTransaction = false;
        }
    }
}
