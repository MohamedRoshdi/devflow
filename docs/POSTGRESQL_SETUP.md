# PostgreSQL Setup Guide for DevFlow Pro

## Quick Start

DevFlow Pro uses PostgreSQL as the primary production database. This guide will help you set up PostgreSQL for your DevFlow Pro installation.

## Prerequisites

- Ubuntu 20.04+ / Debian 10+ / CentOS 8+ / macOS
- PHP 8.4 with PostgreSQL extension
- Composer
- sudo privileges

## Automated Setup (Recommended)

The easiest way to set up PostgreSQL is using the automated setup script:

```bash
cd /path/to/devflow
./scripts/setup-postgresql.sh
```

This script will:
1. Install PostgreSQL (if not already installed)
2. Create the database and user
3. Configure required extensions
4. Update your `.env` file
5. Run migrations
6. Optimize the application

## Manual Setup

If you prefer to set up PostgreSQL manually, follow these steps:

### Step 1: Install PostgreSQL

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install postgresql postgresql-contrib
```

**CentOS/RHEL:**
```bash
sudo yum install postgresql-server postgresql-contrib
sudo postgresql-setup initdb
```

**macOS (Homebrew):**
```bash
brew install postgresql@16
brew services start postgresql@16
```

**Docker:**
```bash
docker-compose -f docker-compose.production.yml up -d postgres
```

### Step 2: Configure PostgreSQL

Start and enable PostgreSQL service:

```bash
sudo systemctl start postgresql
sudo systemctl enable postgresql
```

### Step 3: Create Database and User

```bash
# Switch to postgres user
sudo -u postgres psql

# In PostgreSQL shell, run:
CREATE USER devflow WITH PASSWORD 'your_secure_password';
CREATE DATABASE devflow_pro OWNER devflow;
GRANT ALL PRIVILEGES ON DATABASE devflow_pro TO devflow;

# Connect to the database
\c devflow_pro

# Create required extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";
CREATE EXTENSION IF NOT EXISTS "btree_gin";
CREATE EXTENSION IF NOT EXISTS "btree_gist";
CREATE EXTENSION IF NOT EXISTS "pg_stat_statements";

# Set timezone
ALTER DATABASE devflow_pro SET timezone TO 'UTC';

# Grant schema privileges
GRANT ALL PRIVILEGES ON SCHEMA public TO devflow;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO devflow;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO devflow;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON FUNCTIONS TO devflow;

# Exit PostgreSQL
\q
```

### Step 4: Configure Laravel

Update your `.env` file:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=devflow_pro
DB_USERNAME=devflow
DB_PASSWORD=your_secure_password
```

### Step 5: Run Migrations

```bash
# Clear configuration cache
php artisan config:clear

# Run migrations
php artisan migrate --force

# Seed database (optional)
php artisan db:seed

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 6: Verify Installation

Test database connection:

```bash
php artisan tinker

# In Tinker shell:
DB::connection()->getPdo();
// Should return PDO instance

DB::table('users')->count();
// Should return user count

exit
```

## Docker Setup

DevFlow Pro includes a production-ready Docker Compose configuration with PostgreSQL.

### Using Docker Compose

```bash
# Start all services (PostgreSQL, Redis, App, Nginx)
docker-compose -f docker-compose.production.yml up -d

# View logs
docker-compose -f docker-compose.production.yml logs -f

# Run migrations
docker-compose -f docker-compose.production.yml exec app php artisan migrate --force

# Access PostgreSQL
docker-compose -f docker-compose.production.yml exec postgres psql -U devflow -d devflow_pro

# Stop services
docker-compose -f docker-compose.production.yml down

# Stop and remove volumes (WARNING: deletes data)
docker-compose -f docker-compose.production.yml down -v
```

### Using pgAdmin (Optional)

Start pgAdmin for database management:

```bash
# Start pgAdmin
docker-compose -f docker-compose.production.yml --profile tools up -d pgadmin

# Access pgAdmin at http://localhost:5050
# Default credentials:
#   Email: admin@devflow.pro
#   Password: admin (change in .env)
```

## PostgreSQL Configuration

### Recommended Settings

Edit `/etc/postgresql/16/main/postgresql.conf` (path may vary):

```conf
# Memory Settings
shared_buffers = 256MB              # 25% of RAM
effective_cache_size = 1GB          # 50-75% of RAM
maintenance_work_mem = 64MB
work_mem = 16MB

# Checkpoint Settings
checkpoint_completion_target = 0.9
wal_buffers = 16MB
max_wal_size = 1GB

# Query Planning
random_page_cost = 1.1              # For SSD
effective_io_concurrency = 200

# Logging
log_min_duration_statement = 1000   # Log slow queries (1s+)
log_line_prefix = '%t [%p]: [%l-1] '

# Autovacuum
autovacuum = on
autovacuum_max_workers = 3
```

Restart PostgreSQL after changes:

```bash
sudo systemctl restart postgresql
```

### Performance Tuning

For production servers, consider using PGTune to optimize settings:

https://pgtune.leopard.in.ua/

Select:
- DB Version: PostgreSQL 16
- OS Type: Linux
- DB Type: Web application
- Total Memory: Your server RAM
- Number of CPUs: Your CPU cores
- Number of Connections: 100-200

## Security Hardening

### 1. Configure pg_hba.conf

Edit `/etc/postgresql/16/main/pg_hba.conf`:

```conf
# Local connections
local   all             postgres                                peer
local   devflow_pro     devflow                                 md5

# IPv4 local connections
host    devflow_pro     devflow         127.0.0.1/32            md5

# Allow from Docker network (if using Docker)
host    devflow_pro     devflow         172.16.0.0/12           md5

# Deny all other connections
host    all             all             0.0.0.0/0               reject
```

Reload PostgreSQL:

```bash
sudo systemctl reload postgresql
```

### 2. Use Strong Passwords

Generate a secure password:

```bash
openssl rand -base64 32
```

### 3. Enable SSL (Recommended for Production)

```bash
# Generate SSL certificate
sudo -u postgres openssl req -new -text -nodes \
    -out /var/lib/postgresql/16/main/server.csr \
    -keyout /var/lib/postgresql/16/main/server.key \
    -subj "/CN=devflow-db"

sudo -u postgres openssl x509 -req -in /var/lib/postgresql/16/main/server.csr \
    -text -days 3650 -signkey /var/lib/postgresql/16/main/server.key \
    -out /var/lib/postgresql/16/main/server.crt

sudo chmod 600 /var/lib/postgresql/16/main/server.key
```

Enable SSL in `postgresql.conf`:

```conf
ssl = on
ssl_cert_file = '/var/lib/postgresql/16/main/server.crt'
ssl_key_file = '/var/lib/postgresql/16/main/server.key'
```

Update `.env`:

```env
DB_SSLMODE=require
```

## Backup and Restore

### Automated Backups

DevFlow Pro includes an automated backup script. Add to cron:

```bash
# Daily backup at 2 AM
0 2 * * * /opt/devflow/scripts/postgres_backup.sh >> /opt/devflow/logs/postgres_backup.log 2>&1
```

Create the backup script:

```bash
#!/bin/bash
# /opt/devflow/scripts/postgres_backup.sh

BACKUP_DIR="/opt/devflow/backups/postgres"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
DB_NAME="devflow_pro"
DB_USER="devflow"
RETENTION_DAYS=30

mkdir -p $BACKUP_DIR

# Create backup
PGPASSWORD="$DB_PASSWORD" pg_dump -U $DB_USER -h localhost \
    -d $DB_NAME -Fc -f "$BACKUP_DIR/${DB_NAME}_${TIMESTAMP}.dump"

# Compress old backups
find $BACKUP_DIR -name "*.dump" -mtime +7 -exec gzip {} \;

# Delete old backups
find $BACKUP_DIR -name "*.dump.gz" -mtime +$RETENTION_DAYS -delete

echo "Backup completed: ${DB_NAME}_${TIMESTAMP}.dump"
```

Make it executable:

```bash
chmod +x /opt/devflow/scripts/postgres_backup.sh
```

### Manual Backup

```bash
# Full backup (custom format, compressed)
pg_dump -U devflow -d devflow_pro -Fc -f devflow_backup.dump

# Plain SQL backup
pg_dump -U devflow -d devflow_pro > devflow_backup.sql

# Backup specific tables
pg_dump -U devflow -d devflow_pro -t projects -t servers > partial_backup.sql
```

### Restore

```bash
# Restore from custom format
pg_restore -U devflow -d devflow_pro -c devflow_backup.dump

# Restore from SQL file
psql -U devflow -d devflow_pro < devflow_backup.sql

# Restore to new database
createdb -U devflow devflow_pro_restored
pg_restore -U devflow -d devflow_pro_restored devflow_backup.dump
```

## Monitoring

### Check Database Size

```sql
SELECT
    pg_size_pretty(pg_database_size('devflow_pro')) AS database_size;
```

### Check Table Sizes

```sql
SELECT
    schemaname,
    tablename,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC;
```

### Check Active Connections

```sql
SELECT
    datname,
    count(*) AS connections
FROM pg_stat_activity
GROUP BY datname;
```

### Check Slow Queries

```sql
SELECT
    query,
    calls,
    total_time,
    mean_time,
    max_time
FROM pg_stat_statements
ORDER BY mean_time DESC
LIMIT 10;
```

### Monitor Using pg_top

```bash
# Install pg_top
sudo apt install pg-top

# Monitor PostgreSQL
pg_top -U devflow -d devflow_pro
```

## Troubleshooting

### Connection Refused

```bash
# Check if PostgreSQL is running
sudo systemctl status postgresql

# Check listening ports
sudo netstat -plunt | grep postgres

# Check logs
sudo tail -f /var/log/postgresql/postgresql-16-main.log
```

### Authentication Failed

```bash
# Reset password
sudo -u postgres psql
ALTER USER devflow WITH PASSWORD 'new_password';
\q

# Update .env with new password
```

### Out of Memory

Increase `work_mem` and `shared_buffers` in `postgresql.conf`:

```conf
shared_buffers = 512MB
work_mem = 32MB
```

### Slow Queries

```sql
-- Create indexes for commonly queried columns
CREATE INDEX idx_projects_status ON projects(status);
CREATE INDEX idx_deployments_project_id ON deployments(project_id);
CREATE INDEX idx_deployments_created_at ON deployments(created_at);

-- Analyze tables
ANALYZE;

-- Vacuum
VACUUM ANALYZE;
```

### Disk Space Issues

```bash
# Check disk usage
df -h

# Clear old WAL files (if WAL archiving is not enabled)
sudo -u postgres pg_archivecleanup /var/lib/postgresql/16/main/pg_wal \
    $(ls /var/lib/postgresql/16/main/pg_wal | tail -n 1)
```

## Migration from MySQL

If you're migrating from MySQL, see the comprehensive migration guide:

[docs/POSTGRESQL_MIGRATION.md](./POSTGRESQL_MIGRATION.md)

## Useful Commands

```bash
# Access PostgreSQL shell
psql -U devflow -d devflow_pro

# List databases
\l

# List tables
\dt

# Describe table
\d projects

# Show indexes
\di

# Execute SQL file
\i script.sql

# Copy query results to CSV
\copy (SELECT * FROM projects) TO 'projects.csv' CSV HEADER;

# Show current database
SELECT current_database();

# Show current user
SELECT current_user;

# Show PostgreSQL version
SELECT version();

# Quit
\q
```

## Additional Resources

- **PostgreSQL Documentation**: https://www.postgresql.org/docs/16/
- **Laravel PostgreSQL**: https://laravel.com/docs/database#postgresql
- **PGTune**: https://pgtune.leopard.in.ua/
- **pg_stat_statements**: https://www.postgresql.org/docs/16/pgstatstatements.html
- **PostgreSQL Performance Tuning**: https://wiki.postgresql.org/wiki/Performance_Optimization

## Support

For issues or questions:

1. Check DevFlow Pro documentation: `docs/`
2. Review logs: `/var/log/postgresql/`
3. Check Laravel logs: `storage/logs/laravel.log`
4. Contact support: support@devflow.pro

---

**Note**: Always test database changes in a staging environment before applying to production.
