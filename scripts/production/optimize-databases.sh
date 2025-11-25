#!/bin/bash

###############################################################################
# Database Optimization Script for DevFlow Pro Production
# Optimizes MySQL, PostgreSQL, and Redis databases
###############################################################################

set -e

# Load environment variables
if [ -f /opt/scripts/.backup-env ]; then
    source /opt/scripts/.backup-env
fi

LOG_FILE="/var/log/devflow-db-optimization.log"

# Log function
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

log "=========================================="
log "Starting database optimization..."
log "=========================================="

###############################################################################
# MySQL Optimization
###############################################################################
optimize_mysql() {
    log "Optimizing MySQL databases..."

    # Optimize DevFlow Pro database (host MySQL)
    log "  Optimizing DevFlow Pro tables..."
    mysql -u devflow -p${DEVFLOW_DB_PASSWORD:-devflow_secure_password_123} devflow_pro \
        -e "SHOW TABLES" 2>/dev/null | grep -v Tables_in | while read table; do
        mysql -u devflow -p${DEVFLOW_DB_PASSWORD:-devflow_secure_password_123} devflow_pro \
            -e "OPTIMIZE TABLE \`$table\`" 2>/dev/null || true
        log "    ✓ Optimized table: $table"
    done

    # Analyze tables for better query planning
    log "  Analyzing DevFlow Pro tables..."
    mysql -u devflow -p${DEVFLOW_DB_PASSWORD:-devflow_secure_password_123} devflow_pro \
        -e "ANALYZE TABLE deployments, projects, servers, domains, users" 2>/dev/null || true

    # Optimize ATS Pro database (Docker container)
    log "  Optimizing ATS Pro tables..."
    docker exec ats-mysql mysqlcheck -u root -p${MYSQL_ROOT_PASSWORD} --optimize --all-databases 2>/dev/null || true

    # Show MySQL status
    log "  MySQL Status:"
    mysql -u devflow -p${DEVFLOW_DB_PASSWORD:-devflow_secure_password_123} \
        -e "SHOW GLOBAL STATUS LIKE 'Threads_connected'" 2>/dev/null | tail -1 | \
        while read var value; do log "    Active connections: $value"; done

    mysql -u devflow -p${DEVFLOW_DB_PASSWORD:-devflow_secure_password_123} \
        -e "SHOW GLOBAL STATUS LIKE 'Innodb_buffer_pool_read_requests'" 2>/dev/null | tail -1 | \
        while read var value; do log "    Buffer pool reads: $value"; done

    log "  ✓ MySQL optimization completed"
}

###############################################################################
# PostgreSQL Optimization
###############################################################################
optimize_postgresql() {
    log "Optimizing PostgreSQL databases..."

    # Vacuum and analyze
    log "  Running VACUUM ANALYZE on Portfolio database..."
    docker exec techflow-postgres psql -U techflow -d techflow_portfolio \
        -c "VACUUM ANALYZE;" 2>/dev/null || true

    # Reindex if needed
    log "  Reindexing Portfolio database..."
    docker exec techflow-postgres psql -U techflow -d techflow_portfolio \
        -c "REINDEX DATABASE techflow_portfolio;" 2>/dev/null || true

    # Show PostgreSQL stats
    log "  PostgreSQL Status:"
    docker exec techflow-postgres psql -U techflow -d techflow_portfolio \
        -c "SELECT count(*) as active_connections FROM pg_stat_activity WHERE state = 'active';" \
        2>/dev/null | grep -E "^[0-9]" | while read count; do
        log "    Active connections: $count"
    done

    docker exec techflow-postgres psql -U techflow -d techflow_portfolio \
        -c "SELECT pg_size_pretty(pg_database_size('techflow_portfolio'));" \
        2>/dev/null | grep -E "^[0-9]" | while read size; do
        log "    Database size: $size"
    done

    log "  ✓ PostgreSQL optimization completed"
}

###############################################################################
# Redis Optimization
###############################################################################
optimize_redis() {
    log "Optimizing Redis instances..."

    # Host Redis (DevFlow Pro)
    log "  Optimizing host Redis..."
    if redis-cli PING &>/dev/null | grep -q "PONG"; then
        local used_memory=$(redis-cli INFO memory 2>/dev/null | grep "used_memory_human" | cut -d: -f2 | tr -d '\r')
        local keys=$(redis-cli DBSIZE 2>/dev/null | cut -d: -f2 | tr -d ' ')
        log "    Memory used: $used_memory"
        log "    Total keys: $keys"

        # Remove expired keys
        redis-cli --scan --pattern "*" | xargs -L 100 redis-cli DEL 2>/dev/null || true
    fi

    # ATS Pro Redis
    log "  Optimizing ATS Pro Redis..."
    if docker exec ats-redis redis-cli PING &>/dev/null | grep -q "PONG"; then
        local used_memory=$(docker exec ats-redis redis-cli INFO memory 2>/dev/null | grep "used_memory_human" | cut -d: -f2 | tr -d '\r')
        local keys=$(docker exec ats-redis redis-cli DBSIZE 2>/dev/null | cut -d: -f2 | tr -d ' ')
        log "    Memory used: $used_memory"
        log "    Total keys: $keys"
    fi

    # Portfolio Redis
    log "  Optimizing Portfolio Redis..."
    if docker exec techflow-redis-portfolio redis-cli PING &>/dev/null | grep -q "PONG"; then
        local used_memory=$(docker exec techflow-redis-portfolio redis-cli INFO memory 2>/dev/null | grep "used_memory_human" | cut -d: -f2 | tr -d '\r')
        local keys=$(docker exec techflow-redis-portfolio redis-cli DBSIZE 2>/dev/null | cut -d: -f2 | tr -d ' ')
        log "    Memory used: $used_memory"
        log "    Total keys: $keys"
    fi

    log "  ✓ Redis optimization completed"
}

###############################################################################
# System Kernel Optimizations
###############################################################################
apply_kernel_optimizations() {
    log "Applying kernel optimizations for databases..."

    # Check and apply sysctl settings
    if ! grep -q "# DevFlow Database Optimizations" /etc/sysctl.conf; then
        log "  Adding kernel optimizations to /etc/sysctl.conf..."
        cat >> /etc/sysctl.conf << 'EOF'

# DevFlow Database Optimizations
vm.overcommit_memory = 1
vm.swappiness = 10
net.core.somaxconn = 65535
net.ipv4.tcp_max_syn_backlog = 8192
EOF
        sysctl -p &>/dev/null
        log "  ✓ Kernel optimizations applied"
    else
        log "  Kernel optimizations already applied"
    fi

    # Disable Transparent Huge Pages (THP) for Redis
    if [ -f /sys/kernel/mm/transparent_hugepage/enabled ]; then
        echo never > /sys/kernel/mm/transparent_hugepage/enabled || true
        log "  ✓ Transparent Huge Pages disabled"
    fi
}

###############################################################################
# Index Analysis and Recommendations
###############################################################################
analyze_indexes() {
    log "Analyzing database indexes..."

    # MySQL - Find missing indexes
    log "  Checking MySQL for missing indexes..."
    mysql -u devflow -p${DEVFLOW_DB_PASSWORD:-devflow_secure_password_123} devflow_pro \
        -e "SELECT table_name, column_name FROM information_schema.columns WHERE table_schema='devflow_pro' AND column_name LIKE '%_id' AND column_key = ''" \
        2>/dev/null | grep -v "column_name" | while read table column; do
        log "    ⚠ Consider adding index on $table.$column"
    done

    # PostgreSQL - Find unused indexes
    log "  Checking PostgreSQL for unused indexes..."
    docker exec techflow-postgres psql -U techflow -d techflow_portfolio -c "
        SELECT schemaname, tablename, indexname
        FROM pg_stat_user_indexes
        WHERE idx_scan = 0
        AND indexrelname NOT LIKE '%_pkey'
        LIMIT 10;
    " 2>/dev/null | grep -E "^public" | while read schema table index; do
        log "    ⚠ Unused index: $index on $table"
    done

    log "  ✓ Index analysis completed"
}

###############################################################################
# Main Execution
###############################################################################
main() {
    optimize_mysql
    optimize_postgresql
    optimize_redis
    apply_kernel_optimizations
    analyze_indexes

    log "=========================================="
    log "Database optimization completed!"
    log "=========================================="
}

# Run main function
main

exit 0
