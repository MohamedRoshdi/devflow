---
title: Database Management
description: Database backups, migrations, and management
---

# Database Management

Manage databases, migrations, and backups with DevFlow Pro.

## Database Connections

Configure database connections for projects.

**Supported databases:**

- MySQL
- PostgreSQL
- MariaDB
- MongoDB
- Redis
- SQLite

**Connection information:**

- Host
- Port
- Database name
- Username
- Password
- SSL settings

## Database Migrations

Run database migrations during deployment.

**How it works:**

1. Deployment triggered
2. Code pulled from Git
3. Migrations run automatically
4. Deployment continues

**Migration commands:**

```bash
php artisan migrate
php artisan migrate --force
php artisan migrate:fresh
php artisan migrate:rollback
```

**Safety features:**

- Backup before migration
- Dry-run option
- Rollback on failure
- Migration logs

## Database Backups

Automatic database backups.

**Backup schedule:**

- Hourly backups
- Daily backups
- Weekly backups
- Monthly backups
- Custom schedule

**Backup storage:**

- Local server
- Amazon S3
- Google Cloud Storage
- Dropbox
- Custom S3-compatible storage

**Backup retention:**

- Hourly: 24 hours
- Daily: 7 days
- Weekly: 4 weeks
- Monthly: 12 months
- Custom retention

## Backup Restore

Restore database from backups.

**Restore process:**

1. Select backup to restore
2. Choose restore target
3. Confirm restore
4. Backup restored
5. Application restarted

**Restore options:**

- Full restore
- Partial restore (specific tables)
- Point-in-time restore
- Clone to new database

## Database Query Tool

Execute queries directly.

**Features:**

- Syntax highlighting
- Query autocomplete
- Result export (CSV, JSON)
- Query history
- Saved queries

**Safety:**

- Read-only mode option
- Query approval for production
- Query timeout limits
- Transaction support

## Database Users

Manage database users and permissions.

**Operations:**

- Create user
- Delete user
- Change password
- Grant permissions
- Revoke permissions

**Permission levels:**

- Read-only
- Read-write
- Admin
- Custom permissions

## Database Monitoring

Monitor database performance.

**Metrics:**

- Query count
- Slow queries
- Active connections
- Database size
- Cache hit rate
- Replication lag

**Alerts:**

- Slow query detected
- Connection limit reached
- Replication lag high
- Disk space low

## Database Optimization

Optimize database performance.

**Optimization tasks:**

- Index analysis
- Table optimization
- Query optimization
- Cache configuration
- Connection pooling

**Automated optimization:**

- Scheduled optimization
- Vacuum (PostgreSQL)
- Analyze tables
- Optimize tables

## Database Replication

Configure database replication.

**Replication types:**

- Master-slave
- Master-master
- Circular replication

**Configuration:**

- Set up replica
- Monitor replication lag
- Promote replica to master
- Failover configuration

## Database Import/Export

Import and export database data.

**Import formats:**

- SQL dump
- CSV
- JSON
- XML

**Export formats:**

- SQL dump
- CSV
- JSON
- Excel

**Features:**

- Compress exports
- Encrypt exports
- Schedule exports
- Email exports
