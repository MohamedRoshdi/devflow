# PostgreSQL Migration Guide

## Overview

DevFlow Pro has migrated from MySQL to PostgreSQL as the primary production database. This document provides comprehensive guidance for migrating existing MySQL data to PostgreSQL.

## Why PostgreSQL?

PostgreSQL was chosen as the production database for the following reasons:

1. **Better JSON Support**: Native JSONB type with indexing and advanced querying capabilities
2. **Advanced Data Types**: Support for arrays, hstore, UUID, and custom types
3. **Full-Text Search**: Built-in full-text search without external dependencies
4. **Performance**: Better performance for complex queries and analytics
5. **ACID Compliance**: Stronger consistency and reliability guarantees
6. **Extensibility**: Rich ecosystem of extensions (PostGIS, TimescaleDB, etc.)
7. **Open Source**: Truly open source with no commercial restrictions
8. **Concurrent Writes**: Better handling of concurrent write operations

## Database Configuration

### PostgreSQL Connection Settings

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=devflow_pro
DB_USERNAME=devflow
DB_PASSWORD=your_secure_password
```

### Legacy MySQL Configuration (for reference)

```env
# MySQL connection (deprecated)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=devflow_pro
DB_USERNAME=root
DB_PASSWORD=
```

## Migration Methods

### Method 1: Using pgloader (Recommended)

`pgloader` is a powerful tool that handles MySQL to PostgreSQL migration automatically.

#### Step 1: Install pgloader

**On Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install pgloader
```

**On macOS:**
```bash
brew install pgloader
```

**On CentOS/RHEL:**
```bash
sudo yum install pgloader
```

#### Step 2: Create pgloader Configuration

Create a file named `migrate.load`:

```lisp
LOAD DATABASE
    FROM mysql://root:password@localhost:3306/devflow_pro
    INTO postgresql://devflow:password@localhost:5432/devflow_pro

WITH include drop, create tables, create indexes, reset sequences,
     workers = 8, concurrency = 1,
     multiple readers per thread, rows per range = 50000

SET PostgreSQL PARAMETERS
    maintenance_work_mem to '512MB',
    work_mem to '256MB'

SET MySQL PARAMETERS
    net_read_timeout  = '120',
    net_write_timeout = '120'

CAST type datetime to timestamptz
                drop default drop not null using zero-dates-to-null,
     type date drop not null drop default using zero-dates-to-null,
     type tinyint to boolean using tinyint-to-boolean,
     type year to integer

MATERIALIZE VIEWS
    -- Add any views you need to materialize

BEFORE LOAD DO
    $$ DROP SCHEMA IF EXISTS public CASCADE; $$,
    $$ CREATE SCHEMA public; $$

AFTER LOAD DO
    $$ ALTER DATABASE devflow_pro SET timezone TO 'UTC'; $$;
```

#### Step 3: Run Migration

```bash
# Dry run to check for issues
pgloader --dry-run migrate.load

# Execute migration
pgloader migrate.load
```

#### Step 4: Verify Migration

```bash
# Connect to PostgreSQL
psql -U devflow -d devflow_pro

# Check tables
\dt

# Check row counts
SELECT 'users' as table_name, COUNT(*) FROM users
UNION ALL
SELECT 'projects', COUNT(*) FROM projects
UNION ALL
SELECT 'servers', COUNT(*) FROM servers
UNION ALL
SELECT 'deployments', COUNT(*) FROM deployments
UNION ALL
SELECT 'domains', COUNT(*) FROM domains;

# Check sequences
SELECT sequence_name, last_value FROM information_schema.sequences;
```

### Method 2: Manual Export/Import

#### Step 1: Export MySQL Data

```bash
# Export schema and data
mysqldump -u root -p devflow_pro > devflow_pro_mysql.sql

# Export only data (no schema)
mysqldump -u root -p --no-create-info devflow_pro > devflow_pro_data.sql
```

#### Step 2: Convert MySQL Dump to PostgreSQL Format

Use `mysql2postgresql` or manual conversion:

```bash
# Install mysql2postgresql
pip install mysql-to-postgres

# Convert dump
mysql2psql devflow_pro_mysql.sql devflow_pro_pgsql.sql
```

#### Step 3: Import to PostgreSQL

```bash
# Create database
createdb -U devflow devflow_pro

# Run Laravel migrations first to create schema
php artisan migrate --force

# Import data only
psql -U devflow -d devflow_pro < devflow_pro_data_converted.sql
```

### Method 3: Using Laravel Seeders (Small Datasets)

For small datasets or development environments:

#### Step 1: Export Data from MySQL

```php
// database/seeders/MysqlDataExporter.php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MysqlDataExporter extends Seeder
{
    public function run(): void
    {
        $tables = ['users', 'servers', 'projects', 'deployments', 'domains'];
        $exportPath = storage_path('app/migration');

        File::ensureDirectoryExists($exportPath);

        // Switch to MySQL connection
        config(['database.default' => 'mysql']);

        foreach ($tables as $table) {
            $data = DB::table($table)->get()->toArray();
            File::put(
                "{$exportPath}/{$table}.json",
                json_encode($data, JSON_PRETTY_PRINT)
            );
            $this->command->info("Exported {$table}: " . count($data) . " records");
        }
    }
}
```

#### Step 2: Import Data to PostgreSQL

```php
// database/seeders/PostgresqlDataImporter.php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PostgresqlDataImporter extends Seeder
{
    public function run(): void
    {
        $tables = ['users', 'servers', 'projects', 'deployments', 'domains'];
        $exportPath = storage_path('app/migration');

        // Switch to PostgreSQL connection
        config(['database.default' => 'pgsql']);

        DB::transaction(function () use ($tables, $exportPath) {
            foreach ($tables as $table) {
                $file = "{$exportPath}/{$table}.json";
                if (!File::exists($file)) {
                    $this->command->warn("Skipping {$table}: file not found");
                    continue;
                }

                $data = json_decode(File::get($file), true);

                // Disable triggers temporarily
                DB::statement("ALTER TABLE {$table} DISABLE TRIGGER ALL");

                // Insert data
                foreach (array_chunk($data, 1000) as $chunk) {
                    DB::table($table)->insert($chunk);
                }

                // Re-enable triggers
                DB::statement("ALTER TABLE {$table} ENABLE TRIGGER ALL");

                // Reset sequence
                $this->resetSequence($table);

                $this->command->info("Imported {$table}: " . count($data) . " records");
            }
        });
    }

    private function resetSequence(string $table): void
    {
        $sequence = "{$table}_id_seq";
        DB::statement("SELECT setval('{$sequence}', COALESCE((SELECT MAX(id) FROM {$table}), 1), true)");
    }
}
```

#### Step 3: Run Migration

```bash
# Export from MySQL
php artisan db:seed --class=MysqlDataExporter

# Switch database connection to PostgreSQL in .env
# DB_CONNECTION=pgsql

# Run migrations
php artisan migrate:fresh --force

# Import to PostgreSQL
php artisan db:seed --class=PostgresqlDataImporter
```

## Docker-Based Migration

### Using Docker Compose for Migration

Create `docker-compose.migration.yml`:

```yaml
version: '3.8'

services:
  mysql-source:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: devflow_pro
    volumes:
      - ./backup/mysql:/var/lib/mysql
    ports:
      - "3307:3306"

  postgres-target:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: devflow_pro
      POSTGRES_USER: devflow
      POSTGRES_PASSWORD: devflow
    volumes:
      - ./backup/postgres:/var/lib/postgresql/data
    ports:
      - "5433:5432"

  pgloader:
    image: dimitri/pgloader:latest
    volumes:
      - ./migrate.load:/migrate.load
    command: pgloader /migrate.load
    depends_on:
      - mysql-source
      - postgres-target
```

Run migration:

```bash
docker-compose -f docker-compose.migration.yml up pgloader
```

## Post-Migration Tasks

### 1. Verify Data Integrity

```sql
-- Check all tables exist
SELECT table_name
FROM information_schema.tables
WHERE table_schema = 'public'
ORDER BY table_name;

-- Verify foreign key constraints
SELECT
    tc.table_name,
    tc.constraint_name,
    kcu.column_name,
    ccu.table_name AS foreign_table_name,
    ccu.column_name AS foreign_column_name
FROM information_schema.table_constraints AS tc
JOIN information_schema.key_column_usage AS kcu
    ON tc.constraint_name = kcu.constraint_name
JOIN information_schema.constraint_column_usage AS ccu
    ON ccu.constraint_name = tc.constraint_name
WHERE tc.constraint_type = 'FOREIGN KEY';

-- Check indexes
SELECT
    tablename,
    indexname,
    indexdef
FROM pg_indexes
WHERE schemaname = 'public'
ORDER BY tablename, indexname;
```

### 2. Update Sequences

```sql
-- Reset all sequences to correct values
DO $$
DECLARE
    r RECORD;
BEGIN
    FOR r IN
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = 'public'
        AND table_type = 'BASE TABLE'
    LOOP
        BEGIN
            EXECUTE format('SELECT setval(pg_get_serial_sequence(''%I'', ''id''), COALESCE(MAX(id), 1), true) FROM %I', r.table_name, r.table_name);
        EXCEPTION
            WHEN OTHERS THEN
                -- Skip tables without id or sequence
                NULL;
        END;
    END LOOP;
END $$;
```

### 3. Optimize PostgreSQL

```sql
-- Analyze all tables to update statistics
ANALYZE;

-- Vacuum to reclaim storage
VACUUM FULL;

-- Reindex
REINDEX DATABASE devflow_pro;
```

### 4. Update Application Configuration

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Generate new optimized cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations to ensure schema is up to date
php artisan migrate --force
```

## MySQL to PostgreSQL Syntax Differences

### Data Type Mappings

| MySQL | PostgreSQL | Notes |
|-------|------------|-------|
| `TINYINT(1)` | `BOOLEAN` | MySQL uses TINYINT for booleans |
| `INT AUTO_INCREMENT` | `SERIAL` or `BIGSERIAL` | PostgreSQL uses sequences |
| `DATETIME` | `TIMESTAMP` | PostgreSQL has better timezone support |
| `TEXT` | `TEXT` | Same in both |
| `BLOB` | `BYTEA` | Binary data storage |
| `ENUM('a','b')` | `VARCHAR` with CHECK constraint | PostgreSQL ENUM is less flexible |
| `JSON` | `JSONB` | PostgreSQL JSONB is binary and faster |

### Function Differences

| MySQL | PostgreSQL | Example |
|-------|------------|---------|
| `NOW()` | `NOW()` or `CURRENT_TIMESTAMP` | Same |
| `DATE_FORMAT()` | `TO_CHAR()` | Different syntax |
| `CONCAT()` | `\|\|` or `CONCAT()` | PostgreSQL supports both |
| `IFNULL()` | `COALESCE()` | COALESCE works in both |
| `LIMIT x, y` | `LIMIT y OFFSET x` | Different syntax |
| `ILIKE` | Not available | Use `LOWER()` in MySQL |

### Query Differences

```sql
-- MySQL
SELECT * FROM users LIMIT 10, 20;

-- PostgreSQL
SELECT * FROM users LIMIT 20 OFFSET 10;

-- MySQL
SELECT DATE_FORMAT(created_at, '%Y-%m-%d');

-- PostgreSQL
SELECT TO_CHAR(created_at, 'YYYY-MM-DD');

-- MySQL
SELECT * FROM users WHERE name LIKE '%john%';

-- PostgreSQL (case-insensitive)
SELECT * FROM users WHERE name ILIKE '%john%';
```

## Common Migration Issues

### Issue 1: Zero Dates

MySQL allows `0000-00-00 00:00:00`, PostgreSQL does not.

**Solution:**
```sql
-- Before migration, update MySQL
UPDATE deployments SET started_at = NULL WHERE started_at = '0000-00-00 00:00:00';
```

### Issue 2: Boolean Values

MySQL stores booleans as TINYINT(0/1), PostgreSQL uses true/false.

**Solution:** pgloader handles this automatically with `tinyint-to-boolean` cast.

### Issue 3: Case Sensitivity

PostgreSQL is case-sensitive for object names unless quoted.

**Solution:** Use lowercase for all table and column names, which Laravel does by default.

### Issue 4: String Comparison

PostgreSQL string comparison is case-sensitive by default.

**Solution:** Use `ILIKE` instead of `LIKE` for case-insensitive searches:

```php
// Laravel Query Builder handles this automatically
User::where('name', 'like', '%john%')->get(); // Case-sensitive
User::where('name', 'ilike', '%john%')->get(); // Case-insensitive
```

### Issue 5: Sequence Reset

After data import, sequences may not be set correctly.

**Solution:** Run sequence reset script (see Post-Migration Tasks above).

## Production Deployment Checklist

- [ ] Backup existing MySQL database
- [ ] Test migration in staging environment
- [ ] Verify all data migrated correctly
- [ ] Test application functionality with PostgreSQL
- [ ] Update `.env` with PostgreSQL credentials
- [ ] Update `config/database.php` default connection
- [ ] Run `php artisan migrate:status` to verify migrations
- [ ] Clear all caches: `php artisan optimize:clear`
- [ ] Test critical application features
- [ ] Monitor application logs for errors
- [ ] Set up PostgreSQL backup strategy
- [ ] Configure PostgreSQL performance tuning
- [ ] Update deployment scripts and documentation
- [ ] Train team on PostgreSQL-specific features

## PostgreSQL Optimization

### Recommended postgresql.conf Settings

```conf
# Memory Settings
shared_buffers = 256MB              # 25% of RAM for dedicated server
effective_cache_size = 1GB          # 50-75% of RAM
maintenance_work_mem = 64MB
work_mem = 16MB

# Checkpoint Settings
checkpoint_completion_target = 0.9
wal_buffers = 16MB
max_wal_size = 1GB
min_wal_size = 80MB

# Query Planning
random_page_cost = 1.1              # For SSD storage
effective_io_concurrency = 200      # For SSD storage

# Logging
log_destination = 'stderr'
logging_collector = on
log_directory = 'log'
log_filename = 'postgresql-%Y-%m-%d_%H%M%S.log'
log_min_duration_statement = 1000   # Log queries slower than 1s
log_line_prefix = '%t [%p]: [%l-1] user=%u,db=%d,app=%a,client=%h '

# Autovacuum (important for performance)
autovacuum = on
autovacuum_max_workers = 3
autovacuum_naptime = 1min
```

### Performance Monitoring

```sql
-- Check slow queries
SELECT query, calls, total_time, mean_time
FROM pg_stat_statements
ORDER BY mean_time DESC
LIMIT 10;

-- Check table sizes
SELECT
    schemaname,
    tablename,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC;

-- Check index usage
SELECT
    schemaname,
    tablename,
    indexname,
    idx_scan,
    idx_tup_read,
    idx_tup_fetch
FROM pg_stat_user_indexes
WHERE schemaname = 'public'
ORDER BY idx_scan ASC;
```

## Backup Strategy

### Using pg_dump

```bash
# Backup entire database
pg_dump -U devflow -d devflow_pro > devflow_pro_backup.sql

# Backup with custom format (compressed, faster restore)
pg_dump -U devflow -d devflow_pro -Fc -f devflow_pro_backup.dump

# Backup specific tables
pg_dump -U devflow -d devflow_pro -t projects -t servers > partial_backup.sql

# Restore
psql -U devflow -d devflow_pro < devflow_pro_backup.sql

# Restore from custom format
pg_restore -U devflow -d devflow_pro devflow_pro_backup.dump
```

### Automated Backup Script

```bash
#!/bin/bash
# /opt/devflow/scripts/postgres_backup.sh

BACKUP_DIR="/opt/devflow/backups/postgres"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
DB_NAME="devflow_pro"
DB_USER="devflow"
RETENTION_DAYS=30

# Create backup
pg_dump -U $DB_USER -d $DB_NAME -Fc -f "$BACKUP_DIR/${DB_NAME}_${TIMESTAMP}.dump"

# Compress old backups
find $BACKUP_DIR -name "*.dump" -mtime +7 -exec gzip {} \;

# Delete old backups
find $BACKUP_DIR -name "*.dump.gz" -mtime +$RETENTION_DAYS -delete

echo "Backup completed: ${DB_NAME}_${TIMESTAMP}.dump"
```

### Add to crontab

```bash
# Daily backup at 2 AM
0 2 * * * /opt/devflow/scripts/postgres_backup.sh >> /opt/devflow/logs/postgres_backup.log 2>&1
```

## Support and Resources

- **PostgreSQL Documentation**: https://www.postgresql.org/docs/
- **pgloader Documentation**: https://pgloader.readthedocs.io/
- **Laravel PostgreSQL Guide**: https://laravel.com/docs/database#postgresql
- **PostgreSQL Performance Tuning**: https://wiki.postgresql.org/wiki/Performance_Optimization

## Rollback Plan

If you need to rollback to MySQL:

1. Restore MySQL from backup
2. Update `.env`: `DB_CONNECTION=mysql`
3. Update `config/database.php`: `'default' => 'mysql'`
4. Clear caches: `php artisan optimize:clear`
5. Restart application services

**Note:** Always test migration in staging before production deployment.
