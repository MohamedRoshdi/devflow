# PostgreSQL Quick Reference - DevFlow Pro

## Quick Commands

### Docker Deployment (Recommended)

```bash
# Start all services
docker-compose -f docker-compose.production.yml up -d

# Stop all services
docker-compose -f docker-compose.production.yml down

# View logs
docker-compose -f docker-compose.production.yml logs -f

# Run migrations
docker-compose -f docker-compose.production.yml exec app php artisan migrate --force

# Access PostgreSQL
docker-compose -f docker-compose.production.yml exec postgres psql -U devflow -d devflow_pro

# Access application shell
docker-compose -f docker-compose.production.yml exec app bash

# Restart specific service
docker-compose -f docker-compose.production.yml restart app

# View service status
docker-compose -f docker-compose.production.yml ps
```

### Manual Setup

```bash
# Run automated setup
./scripts/setup-postgresql.sh

# Or manually
sudo apt install postgresql postgresql-contrib
sudo -u postgres psql
# Then create database and user (see POSTGRESQL_SETUP.md)
```

### Database Operations

```bash
# Connect to database
psql -U devflow -d devflow_pro

# Backup database
pg_dump -U devflow -d devflow_pro -Fc -f backup.dump

# Restore database
pg_restore -U devflow -d devflow_pro -c backup.dump

# Import SQL file
psql -U devflow -d devflow_pro < backup.sql
```

### Laravel Commands

```bash
# Run migrations
php artisan migrate --force

# Rollback migrations
php artisan migrate:rollback

# Check migration status
php artisan migrate:status

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

## SQL Quick Reference

### Database Info

```sql
-- Current database
SELECT current_database();

-- Database size
SELECT pg_size_pretty(pg_database_size('devflow_pro'));

-- List all tables
\dt

-- Describe table
\d projects

-- Show indexes
\di

-- Show table size
SELECT pg_size_pretty(pg_total_relation_size('projects'));
```

### Common Queries

```sql
-- Active connections
SELECT count(*) FROM pg_stat_activity WHERE datname = 'devflow_pro';

-- Kill connection
SELECT pg_terminate_backend(pid) FROM pg_stat_activity
WHERE datname = 'devflow_pro' AND pid <> pg_backend_pid();

-- Table sizes
SELECT
    schemaname,
    tablename,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC;

-- Slow queries
SELECT query, calls, total_time, mean_time
FROM pg_stat_statements
ORDER BY mean_time DESC
LIMIT 10;

-- Unused indexes
SELECT
    schemaname || '.' || tablename AS table,
    indexname AS index,
    pg_size_pretty(pg_relation_size(i.indexrelid)) AS index_size,
    idx_scan as index_scans
FROM pg_stat_user_indexes ui
JOIN pg_index i ON ui.indexrelid = i.indexrelid
WHERE NOT indisunique
ORDER BY pg_relation_size(i.indexrelid) DESC;
```

### Maintenance

```sql
-- Analyze tables (update statistics)
ANALYZE;

-- Vacuum and analyze
VACUUM ANALYZE;

-- Reindex database
REINDEX DATABASE devflow_pro;

-- Reset sequences
SELECT setval(pg_get_serial_sequence('projects', 'id'),
    COALESCE((SELECT MAX(id) FROM projects), 1), true);
```

## Configuration Files

### Environment (.env)
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=devflow_pro
DB_USERNAME=devflow
DB_PASSWORD=your_secure_password
```

### PostgreSQL (postgresql.conf)
```conf
shared_buffers = 256MB
effective_cache_size = 1GB
work_mem = 16MB
maintenance_work_mem = 64MB
random_page_cost = 1.1
```

### Connection Limits (pg_hba.conf)
```conf
local   devflow_pro     devflow                                 md5
host    devflow_pro     devflow         127.0.0.1/32            md5
```

## Monitoring

### Check Performance

```sql
-- Cache hit ratio (should be > 99%)
SELECT
    sum(heap_blks_read) as heap_read,
    sum(heap_blks_hit) as heap_hit,
    sum(heap_blks_hit) / (sum(heap_blks_hit) + sum(heap_blks_read)) as ratio
FROM pg_statio_user_tables;

-- Index usage
SELECT
    schemaname,
    tablename,
    indexname,
    idx_scan,
    idx_tup_read,
    idx_tup_fetch
FROM pg_stat_user_indexes
WHERE schemaname = 'public'
ORDER BY idx_scan DESC;

-- Bloat check
SELECT
    schemaname || '.' || tablename AS table,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC;
```

### Logs

```bash
# PostgreSQL logs
tail -f /var/log/postgresql/postgresql-16-main.log

# Laravel logs
tail -f storage/logs/laravel.log

# Docker logs
docker-compose -f docker-compose.production.yml logs -f postgres
```

## Backup & Restore

### Quick Backup

```bash
# Full backup (compressed)
pg_dump -U devflow -d devflow_pro -Fc -f devflow_$(date +%Y%m%d).dump

# SQL format
pg_dump -U devflow -d devflow_pro > devflow_$(date +%Y%m%d).sql

# Specific tables
pg_dump -U devflow -d devflow_pro -t projects -t servers > partial.sql
```

### Quick Restore

```bash
# From custom format
pg_restore -U devflow -d devflow_pro -c devflow_backup.dump

# From SQL file
psql -U devflow -d devflow_pro < devflow_backup.sql

# To new database
createdb -U devflow devflow_pro_new
pg_restore -U devflow -d devflow_pro_new devflow_backup.dump
```

## Troubleshooting

### Connection Issues

```bash
# Check if PostgreSQL is running
sudo systemctl status postgresql

# Check listening ports
sudo netstat -plunt | grep postgres

# Test connection
psql -U devflow -d devflow_pro -h localhost -p 5432
```

### Performance Issues

```sql
-- Find slow queries
SELECT
    pid,
    now() - query_start as duration,
    query
FROM pg_stat_activity
WHERE state = 'active'
ORDER BY duration DESC;

-- Kill slow query
SELECT pg_cancel_backend(pid);
-- or
SELECT pg_terminate_backend(pid);

-- Check locks
SELECT * FROM pg_locks WHERE NOT granted;
```

### Disk Space

```bash
# Check disk usage
df -h /var/lib/postgresql

# Check database sizes
sudo -u postgres psql -c "SELECT pg_database.datname,
    pg_size_pretty(pg_database_size(pg_database.datname))
    FROM pg_database ORDER BY pg_database_size(pg_database.datname) DESC;"

# Clean old WAL files (if not using archiving)
sudo -u postgres find /var/lib/postgresql/16/main/pg_wal -type f -mtime +7 -delete
```

## Environment-Specific Settings

### Development
```env
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=pgsql
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
```

### Staging
```env
APP_ENV=staging
APP_DEBUG=true
DB_CONNECTION=pgsql
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Production
```env
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=pgsql
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

## Security Checklist

- [ ] Change default PostgreSQL password
- [ ] Configure pg_hba.conf to restrict access
- [ ] Enable SSL/TLS connections
- [ ] Regular security updates
- [ ] Strong password (32+ characters)
- [ ] Firewall rules configured
- [ ] Regular backups scheduled
- [ ] Monitor failed login attempts

## Common File Locations

```
Config:      /etc/postgresql/16/main/postgresql.conf
Access:      /etc/postgresql/16/main/pg_hba.conf
Data:        /var/lib/postgresql/16/main
Logs:        /var/log/postgresql
Backups:     /opt/devflow/backups/postgres
Scripts:     /opt/devflow/scripts
```

## Useful psql Commands

```bash
\?              # Help
\l              # List databases
\c dbname       # Connect to database
\dt             # List tables
\d tablename    # Describe table
\di             # List indexes
\du             # List users
\dn             # List schemas
\df             # List functions
\dv             # List views
\timing         # Toggle timing
\x              # Toggle expanded output
\q              # Quit
\i file.sql     # Execute SQL file
\o file.txt     # Output to file
\! command      # Execute shell command
```

## Key PostgreSQL Extensions

```sql
-- UUID generation
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Full-text search
CREATE EXTENSION IF NOT EXISTS "pg_trgm";

-- Better indexing
CREATE EXTENSION IF NOT EXISTS "btree_gin";
CREATE EXTENSION IF NOT EXISTS "btree_gist";

-- Query statistics
CREATE EXTENSION IF NOT EXISTS "pg_stat_statements";

-- Show installed extensions
\dx
```

## Documentation Links

- Setup Guide: `docs/POSTGRESQL_SETUP.md`
- Migration Guide: `docs/POSTGRESQL_MIGRATION.md`
- Configuration Summary: `POSTGRESQL_CONFIGURATION_SUMMARY.md`
- PostgreSQL Docs: https://www.postgresql.org/docs/16/
- Laravel Database: https://laravel.com/docs/database

## Emergency Contacts

- PostgreSQL Issues: Check logs at `/var/log/postgresql/`
- Laravel Issues: Check logs at `storage/logs/laravel.log`
- Docker Issues: Run `docker-compose logs -f`

---

**Last Updated**: 2025-12-09
**PostgreSQL Version**: 16
**Laravel Version**: 12
**PHP Version**: 8.4
