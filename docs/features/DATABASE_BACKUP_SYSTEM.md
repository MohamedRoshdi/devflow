# Database Backup Management System - Implementation Guide

## Overview

A comprehensive database backup management system for DevFlow Pro with automated scheduling, integrity verification, intelligent retention policies, and multi-storage support.

## Features Implemented

### 1. Enhanced Database Schema

#### Migrations
- **2025_12_03_000002_enhance_database_backups_table.php**
  - Added `checksum` (SHA-256 hash for integrity verification)
  - Added `type` field (manual, scheduled, pre_deploy)
  - Added `verified_at` timestamp for tracking verification
  - Added `metadata` JSON field for database info (tables count, size, etc.)

#### Backup Schedules Enhancement
- **retention_daily** (default: 7) - Keep last N daily backups
- **retention_weekly** (default: 4) - Keep last N weekly backups
- **retention_monthly** (default: 3) - Keep last N monthly backups
- **encrypt** - Option to encrypt backup files
- **notify_on_failure** - Send notifications on backup failures

### 2. Enhanced Models

#### DatabaseBackup Model (`/home/roshdy/Work/projects/DEVFLOW_PRO/app/Models/DatabaseBackup.php`)
New methods:
- `isVerified()` - Check if backup has been verified
- `isPending()`, `isRunning()`, `isCompleted()`, `isFailed()` - Status helpers
- `getStatusColorAttribute()` - Get color for status badges
- `getStatusIconAttribute()` - Get icon for status display

New scopes:
- `scopeVerified()` - Filter verified backups
- `scopeUnverified()` - Filter unverified backups

#### BackupSchedule Model (`/home/roshdy/Work/projects/DEVFLOW_PRO/app/Models/BackupSchedule.php`)
Enhanced with new retention policy fields and encryption options.

### 3. Enhanced DatabaseBackupService

**Location:** `/home/roshdy/Work/projects/DEVFLOW_PRO/app/Services/DatabaseBackupService.php`

#### New Features

**Checksum Verification:**
```php
public function verifyBackup(DatabaseBackup $backup): bool
```
- Calculates SHA-256 checksum of backup file
- Compares with stored checksum
- Updates `verified_at` timestamp on success
- Returns true/false for validation result

**Enhanced Retention Policy:**
```php
public function applyRetentionPolicy(Project $project): int
```
- Implements intelligent backup retention
- Keeps daily backups (last 7 days)
- Keeps weekly backups (one per week, last 4 weeks)
- Keeps monthly backups (one per month, last 3 months)
- Automatically removes old backups based on policy

**Backup Metadata Collection:**
- MySQL: Collects table count and database size
- PostgreSQL: Stores backup method information
- SQLite: Records file-based backup details

**Optional Encryption:**
```php
protected function encryptBackup(string $filePath): string
```
- AES-256-CBC encryption using app key
- Stores encrypted backups with `.enc` extension

### 4. Artisan Commands

#### BackupDatabase Command
**Usage:** `php artisan backup:database [project] [--type=manual] [--database=]`

Examples:
```bash
# Backup all projects
php artisan backup:database

# Backup specific project by slug
php artisan backup:database my-project

# Backup specific database
php artisan backup:database my-project --database=my_database

# Create pre-deployment backup
php artisan backup:database my-project --type=pre_deploy
```

**Location:** `/home/roshdy/Work/projects/DEVFLOW_PRO/app/Console/Commands/BackupDatabase.php`

#### CleanupBackups Command
**Usage:** `php artisan backup:cleanup [project] [--dry-run]`

Examples:
```bash
# Cleanup all projects
php artisan backup:cleanup

# Cleanup specific project
php artisan backup:cleanup my-project

# Dry run (preview what would be deleted)
php artisan backup:cleanup --dry-run
```

**Location:** `/home/roshdy/Work/projects/DEVFLOW_PRO/app/Console/Commands/CleanupBackups.php`

#### VerifyBackup Command
**Usage:** `php artisan backup:verify [backup] [--all] [--project=]`

Examples:
```bash
# Verify specific backup
php artisan backup:verify 123

# Verify all unverified backups
php artisan backup:verify --all

# Verify all backups for a project
php artisan backup:verify --project=my-project
```

**Location:** `/home/roshdy/Work/projects/DEVFLOW_PRO/app/Console/Commands/VerifyBackup.php`

### 5. Scheduler Integration

**Location:** `/home/roshdy/Work/projects/DEVFLOW_PRO/routes/console.php`

```php
// Run scheduled database backups (every 15 minutes)
Schedule::command('backups:run')->everyFifteenMinutes();

// Clean up old backups (daily at midnight)
Schedule::command('backup:cleanup')->daily()->at('00:00');
```

### 6. Livewire Component Enhancements

**Location:** `/home/roshdy/Work/projects/DEVFLOW_PRO/app/Livewire/Projects/DatabaseBackupManager.php`

#### New Features:
- **Backup Verification:**
  - Click shield icon to verify backup integrity
  - Shows checkmark for verified backups
  - Real-time verification with progress indicator

- **Visual Indicators:**
  - Verified backups show green shield-check icon
  - Unverified backups show purple shield icon
  - Hover tooltips show verification timestamp

### 7. Blade Template Updates

**Location:** `/home/roshdy/Work/projects/DEVFLOW_PRO/resources/views/livewire/projects/database-backup-manager.blade.php`

#### New UI Elements:
- Verification button/icon in actions column
- Verification confirmation modal
- Visual feedback for verified backups
- Improved status indicators

## Database Schema

### database_backups Table
```sql
- id (bigint)
- project_id (foreign key)
- server_id (foreign key)
- database_type (enum: mysql, postgresql, sqlite)
- database_name (string)
- type (enum: manual, scheduled, pre_deploy)
- file_name (string)
- file_path (string)
- file_size (bigint)
- checksum (string, 64 chars) - SHA-256 hash
- storage_disk (enum: local, s3)
- status (enum: pending, running, completed, failed)
- started_at (timestamp)
- completed_at (timestamp)
- verified_at (timestamp)
- error_message (text)
- metadata (json)
- created_at (timestamp)
```

### backup_schedules Table
```sql
- id (bigint)
- project_id (foreign key)
- server_id (foreign key)
- database_type (enum: mysql, postgresql, sqlite)
- database_name (string)
- frequency (enum: hourly, daily, weekly, monthly)
- time (time)
- day_of_week (tinyint, 0-6)
- day_of_month (tinyint, 1-31)
- retention_days (int)
- retention_daily (int, default: 7)
- retention_weekly (int, default: 4)
- retention_monthly (int, default: 3)
- storage_disk (enum: local, s3)
- encrypt (boolean, default: false)
- notify_on_failure (boolean, default: true)
- is_active (boolean)
- last_run_at (timestamp)
- next_run_at (timestamp)
- created_at (timestamp)
- updated_at (timestamp)
```

## Usage Examples

### Create Manual Backup via Livewire UI
1. Navigate to project's backup management page
2. Click "Create Backup Now" button
3. Select database type and enter database name
4. Click "Create Backup"
5. System creates backup and calculates checksum

### Verify Backup Integrity
1. View backup list
2. Click purple shield icon for unverified backup
3. Confirm verification
4. System validates checksum and updates status

### Schedule Automated Backups
1. Click "Create Schedule" button
2. Configure:
   - Database type and name
   - Frequency (hourly, daily, weekly, monthly)
   - Time and day settings
   - Retention policy (daily, weekly, monthly counts)
   - Storage location (local or S3)
   - Optional encryption
3. Save schedule
4. Backups run automatically based on schedule

### Restore from Backup
1. Find backup in list
2. Click restore icon (undo arrow)
3. Confirm restoration (warning about data loss)
4. System restores database from backup

## Retention Policy Logic

The intelligent retention system keeps:

1. **Daily Backups:** Last 7 daily backups (configurable)
2. **Weekly Backups:** One backup per week for last 4 weeks (configurable)
3. **Monthly Backups:** One backup per month for last 3 months (configurable)

This ensures:
- Recent backups for quick recovery (daily)
- Medium-term recovery points (weekly)
- Long-term archival (monthly)
- Automatic cleanup of old backups
- Optimized storage usage

## Security Features

### Checksum Verification
- SHA-256 hash calculated for each backup
- Stored in database for later verification
- Detects file corruption or tampering
- Automatic verification available

### Optional Encryption
- AES-256-CBC encryption
- Uses Laravel application key
- Transparent encryption/decryption
- Secure backup storage

### Access Control
- Project-level authorization
- User permissions enforced
- Audit logging for all operations

## Storage Support

### Local Storage
- Stored in `storage/app/backups/YYYY/MM/DD/`
- Organized by date for easy management
- Automatic directory creation

### S3 Storage
- Uploaded to S3 after creation
- Path: `backups/YYYY/MM/DD/filename`
- Local copy removed after upload
- Download on-demand for restore

## Monitoring & Logging

All backup operations are logged:
- Backup creation (success/failure)
- Verification results
- Cleanup operations
- Restoration activities

Log entries include:
- Project ID
- Database name
- File size
- Checksum
- Duration
- Error messages (if any)

## Performance Considerations

### Large Databases
- 1-hour timeout for mysqldump operations
- Streaming file handling
- Progress indicators in UI
- Background job processing

### Storage Optimization
- Gzip compression (reduces size by 70-90%)
- Optional encryption
- Intelligent retention cleanup
- S3 lifecycle policies supported

## Migration Guide

To apply these enhancements:

```bash
# 1. Run migration
php artisan migrate

# 2. Verify commands are available
php artisan list backup

# 3. Test manual backup
php artisan backup:database my-project --type=manual

# 4. Test verification
php artisan backup:verify --all

# 5. Test cleanup (dry run first)
php artisan backup:cleanup --dry-run
```

## Troubleshooting

### Verification Fails
- Check file exists in storage
- Verify storage disk is accessible
- Check file permissions
- Review error logs

### Backup Fails
- Verify SSH connection to server
- Check database credentials
- Ensure sufficient disk space
- Review mysqldump/pg_dump availability

### Cleanup Issues
- Check retention policy settings
- Verify backup records in database
- Review filesystem permissions
- Check S3 credentials (if using S3)

## Future Enhancements

Potential additions:
- Multi-region S3 replication
- Backup compression level settings
- Custom retention policies per schedule
- Email notifications for failures
- Backup size trends and analytics
- Automated restore testing
- Point-in-time recovery support

## Files Modified/Created

### New Files:
1. `/home/roshdy/Work/projects/DEVFLOW_PRO/database/migrations/2025_12_03_000002_enhance_database_backups_table.php`
2. `/home/roshdy/Work/projects/DEVFLOW_PRO/app/Console/Commands/BackupDatabase.php`
3. `/home/roshdy/Work/projects/DEVFLOW_PRO/app/Console/Commands/CleanupBackups.php`
4. `/home/roshdy/Work/projects/DEVFLOW_PRO/app/Console/Commands/VerifyBackup.php`

### Modified Files:
1. `/home/roshdy/Work/projects/DEVFLOW_PRO/app/Models/DatabaseBackup.php`
2. `/home/roshdy/Work/projects/DEVFLOW_PRO/app/Models/BackupSchedule.php`
3. `/home/roshdy/Work/projects/DEVFLOW_PRO/app/Services/DatabaseBackupService.php`
4. `/home/roshdy/Work/projects/DEVFLOW_PRO/app/Livewire/Projects/DatabaseBackupManager.php`
5. `/home/roshdy/Work/projects/DEVFLOW_PRO/resources/views/livewire/projects/database-backup-manager.blade.php`
6. `/home/roshdy/Work/projects/DEVFLOW_PRO/routes/console.php`

## Testing Checklist

- [ ] Migration runs successfully
- [ ] Manual backup creation works
- [ ] Scheduled backup execution works
- [ ] Backup verification validates checksums
- [ ] Retention policy cleanup works correctly
- [ ] S3 upload/download functions properly
- [ ] UI displays verification status
- [ ] Restore functionality works
- [ ] All Artisan commands execute correctly
- [ ] Logging captures all operations
