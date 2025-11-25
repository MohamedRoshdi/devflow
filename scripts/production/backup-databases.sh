#!/bin/bash

###############################################################################
# Database Backup Script for DevFlow Pro Production
# Automated backup for MySQL, PostgreSQL, and Redis
# Retention: 7 daily, 4 weekly, 12 monthly backups
###############################################################################

set -e

# Load environment variables
if [ -f /opt/scripts/.backup-env ]; then
    source /opt/scripts/.backup-env
fi

# Configuration
BACKUP_BASE_DIR="/opt/backups"
BACKUP_DIR="${BACKUP_BASE_DIR}/databases"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAILY=7
RETENTION_WEEKLY=4
RETENTION_MONTHLY=12

# Create backup directories
mkdir -p "${BACKUP_DIR}"/{daily,weekly,monthly}/{mysql,postgresql,redis}

# Log function
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" | tee -a "${BACKUP_DIR}/backup.log"
}

log "Starting database backup process..."

###############################################################################
# MySQL Backup (DevFlow Pro from host + ATS Pro from container)
###############################################################################
backup_mysql() {
    log "Backing up MySQL databases..."

    # DevFlow Pro database (from host MySQL)
    if mysqldump -u devflow -p${DEVFLOW_DB_PASSWORD:-devflow_secure_password_123} devflow_pro 2>/dev/null | gzip > "${BACKUP_DIR}/daily/mysql/devflow_pro_${DATE}.sql.gz"; then
        log "✓ DevFlow Pro MySQL backup completed"
    else
        log "✗ DevFlow Pro MySQL backup failed"
    fi

    # ATS Pro database (from Docker container)
    if docker exec ats-mysql mysqldump -u root -p${MYSQL_ROOT_PASSWORD} ats_pro 2>/dev/null | gzip > "${BACKUP_DIR}/daily/mysql/ats_pro_${DATE}.sql.gz"; then
        log "✓ ATS Pro MySQL backup completed"
    else
        log "✗ ATS Pro MySQL backup failed"
    fi
}

###############################################################################
# PostgreSQL Backup (Portfolio)
###############################################################################
backup_postgresql() {
    log "Backing up PostgreSQL databases..."

    if docker exec techflow-postgres pg_dump -U techflow techflow_portfolio 2>/dev/null | gzip > "${BACKUP_DIR}/daily/postgresql/portfolio_${DATE}.sql.gz"; then
        log "✓ Portfolio PostgreSQL backup completed"
    else
        log "✗ Portfolio PostgreSQL backup failed"
    fi
}

###############################################################################
# Redis Backup (All applications)
###############################################################################
backup_redis() {
    log "Backing up Redis data..."

    # DevFlow Pro Redis (host Redis)
    if redis-cli SAVE 2>/dev/null && \
       cp /var/lib/redis/dump.rdb "${BACKUP_DIR}/daily/redis/devflow_${DATE}.rdb" 2>/dev/null; then
        log "✓ DevFlow Pro Redis backup completed"
    else
        log "✗ DevFlow Pro Redis backup failed"
    fi

    # ATS Pro Redis (container)
    if docker exec ats-redis redis-cli SAVE 2>/dev/null && \
       docker cp ats-redis:/data/dump.rdb "${BACKUP_DIR}/daily/redis/ats_${DATE}.rdb" 2>/dev/null; then
        log "✓ ATS Pro Redis backup completed"
    else
        log "✗ ATS Pro Redis backup failed"
    fi

    # Portfolio Redis (container)
    if docker exec techflow-redis-portfolio redis-cli SAVE 2>/dev/null && \
       docker cp techflow-redis-portfolio:/data/dump.rdb "${BACKUP_DIR}/daily/redis/portfolio_${DATE}.rdb" 2>/dev/null; then
        log "✓ Portfolio Redis backup completed"
    else
        log "✗ Portfolio Redis backup failed"
    fi
}

###############################################################################
# Weekly and Monthly Rotation
###############################################################################
rotate_backups() {
    log "Rotating backups..."

    DAY_OF_WEEK=$(date +%u)  # 1-7 (Monday-Sunday)
    DAY_OF_MONTH=$(date +%d)

    # Copy to weekly backup on Sunday
    if [ "$DAY_OF_WEEK" -eq 7 ]; then
        log "Creating weekly backups..."
        cp -r "${BACKUP_DIR}/daily/"* "${BACKUP_DIR}/weekly/" 2>/dev/null || true
    fi

    # Copy to monthly backup on 1st of month
    if [ "$DAY_OF_MONTH" -eq 01 ]; then
        log "Creating monthly backups..."
        cp -r "${BACKUP_DIR}/daily/"* "${BACKUP_DIR}/monthly/" 2>/dev/null || true
    fi

    # Clean up old daily backups (keep last 7 days)
    log "Cleaning up old daily backups..."
    find "${BACKUP_DIR}/daily" -type f -mtime +${RETENTION_DAILY} -delete 2>/dev/null || true

    # Clean up old weekly backups (keep last 4 weeks)
    log "Cleaning up old weekly backups..."
    find "${BACKUP_DIR}/weekly" -type f -mtime +$((RETENTION_WEEKLY * 7)) -delete 2>/dev/null || true

    # Clean up old monthly backups (keep last 12 months)
    log "Cleaning up old monthly backups..."
    find "${BACKUP_DIR}/monthly" -type f -mtime +$((RETENTION_MONTHLY * 30)) -delete 2>/dev/null || true
}

###############################################################################
# S3 Upload (Optional - uncomment if using S3)
###############################################################################
upload_to_s3() {
    if [ -n "$AWS_S3_BUCKET" ]; then
        log "Uploading backups to S3..."
        if command -v aws &> /dev/null; then
            aws s3 sync "${BACKUP_DIR}/daily" "s3://${AWS_S3_BUCKET}/backups/daily/" --delete
            aws s3 sync "${BACKUP_DIR}/weekly" "s3://${AWS_S3_BUCKET}/backups/weekly/" --delete
            aws s3 sync "${BACKUP_DIR}/monthly" "s3://${AWS_S3_BUCKET}/backups/monthly/" --delete
            log "✓ S3 upload completed"
        else
            log "⚠ AWS CLI not installed, skipping S3 upload"
        fi
    fi
}

###############################################################################
# Backup Verification
###############################################################################
verify_backups() {
    log "Verifying backups..."

    BACKUP_COUNT=$(find "${BACKUP_DIR}/daily" -type f -mtime -1 2>/dev/null | wc -l)

    if [ "$BACKUP_COUNT" -lt 3 ]; then
        log "⚠ Warning: Only $BACKUP_COUNT backup files created in the last 24 hours"
        # Send alert (implement your alerting system here)
    else
        log "✓ Backup verification passed: $BACKUP_COUNT files created"
    fi
}

###############################################################################
# Main Execution
###############################################################################
main() {
    log "=========================================="
    log "Starting backup process for $(hostname)"
    log "=========================================="

    # Execute backups
    backup_mysql
    backup_postgresql
    backup_redis

    # Rotate and cleanup
    rotate_backups

    # Optional S3 upload
    # upload_to_s3

    # Verify
    verify_backups

    # Calculate backup size
    BACKUP_SIZE=$(du -sh "${BACKUP_DIR}" 2>/dev/null | cut -f1)
    log "Total backup size: ${BACKUP_SIZE}"

    log "=========================================="
    log "Backup process completed successfully!"
    log "=========================================="
}

# Run main function
main

exit 0
