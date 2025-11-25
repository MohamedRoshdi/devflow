#!/bin/bash

###############################################################################
# System Health Monitoring Script for DevFlow Pro Production
# Monitors: Disk space, CPU, Memory, Docker containers, Database connections
# Sends alerts when thresholds are exceeded
###############################################################################

set -e

# Load environment variables
if [ -f /opt/scripts/.monitor-env ]; then
    source /opt/scripts/.monitor-env
fi

# Configuration
LOG_FILE="/var/log/devflow-monitor.log"
ALERT_EMAIL="${ALERT_EMAIL:-admin@devflow.com}"
ALERT_WEBHOOK="${ALERT_WEBHOOK:-}"

# Thresholds
DISK_THRESHOLD=80
CPU_THRESHOLD=80
MEMORY_THRESHOLD=85
CONTAINER_RESTART_THRESHOLD=5

# Colors for output
RED='\033[0;31m'
YELLOW='\033[1;33m'
GREEN='\033[0;32m'
NC='\033[0m' # No Color

# Log function
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Alert function
send_alert() {
    local severity=$1
    local message=$2

    log "[$severity] $message"

    # Send email alert if configured
    if command -v mail &> /dev/null && [ -n "$ALERT_EMAIL" ]; then
        echo "$message" | mail -s "DevFlow Pro Alert: $severity" "$ALERT_EMAIL"
    fi

    # Send webhook alert if configured
    if [ -n "$ALERT_WEBHOOK" ]; then
        curl -X POST "$ALERT_WEBHOOK" \
            -H "Content-Type: application/json" \
            -d "{\"severity\": \"$severity\", \"message\": \"$message\", \"timestamp\": \"$(date -Iseconds)\"}" \
            2>/dev/null || true
    fi
}

###############################################################################
# System Metrics
###############################################################################

check_disk_space() {
    log "Checking disk space..."

    local critical=0
    while IFS= read -r line; do
        local mount=$(echo "$line" | awk '{print $6}')
        local usage=$(echo "$line" | awk '{print $5}' | sed 's/%//')

        if [ "$usage" -ge "$DISK_THRESHOLD" ]; then
            send_alert "WARNING" "Disk usage on $mount is ${usage}% (threshold: ${DISK_THRESHOLD}%)"
            critical=1
        else
            log "  ✓ Disk usage on $mount: ${usage}%"
        fi
    done < <(df -h | grep '^/dev' | awk '$5+0 > 0')

    return $critical
}

check_cpu_usage() {
    log "Checking CPU usage..."

    local cpu_usage=$(top -bn1 | grep "Cpu(s)" | sed "s/.*, *\([0-9.]*\)%* id.*/\1/" | awk '{print 100 - $1}' | cut -d'.' -f1)

    if [ "$cpu_usage" -ge "$CPU_THRESHOLD" ]; then
        send_alert "WARNING" "CPU usage is ${cpu_usage}% (threshold: ${CPU_THRESHOLD}%)"
        return 1
    else
        log "  ✓ CPU usage: ${cpu_usage}%"
        return 0
    fi
}

check_memory_usage() {
    log "Checking memory usage..."

    local mem_usage=$(free | grep Mem | awk '{print int(($3/$2) * 100)}')

    if [ "$mem_usage" -ge "$MEMORY_THRESHOLD" ]; then
        send_alert "WARNING" "Memory usage is ${mem_usage}% (threshold: ${MEMORY_THRESHOLD}%)"
        return 1
    else
        log "  ✓ Memory usage: ${mem_usage}%"
        return 0
    fi
}

###############################################################################
# Docker Container Health
###############################################################################

check_docker_containers() {
    log "Checking Docker containers..."

    local critical=0
    local containers=(
        "ats-app"
        "ats-mysql"
        "ats-redis"
        "ats-nginx"
        "ats-horizon"
        "techflow-portfolio"
        "techflow-postgres"
        "techflow-redis-portfolio"
    )

    for container in "${containers[@]}"; do
        if docker ps --format '{{.Names}}' | grep -q "^${container}$"; then
            local status=$(docker inspect --format='{{.State.Status}}' "$container")
            local health=$(docker inspect --format='{{.State.Health.Status}}' "$container" 2>/dev/null || echo "N/A")
            local restarts=$(docker inspect --format='{{.RestartCount}}' "$container")

            if [ "$status" != "running" ]; then
                send_alert "CRITICAL" "Container $container is not running (status: $status)"
                critical=1
            elif [ "$restarts" -ge "$CONTAINER_RESTART_THRESHOLD" ]; then
                send_alert "WARNING" "Container $container has restarted $restarts times"
                critical=1
            elif [ "$health" != "N/A" ] && [ "$health" != "healthy" ]; then
                send_alert "WARNING" "Container $container health check failed (health: $health)"
                critical=1
            else
                log "  ✓ Container $container: running (health: $health, restarts: $restarts)"
            fi
        else
            send_alert "CRITICAL" "Container $container is not found"
            critical=1
        fi
    done

    return $critical
}

###############################################################################
# Database Connections
###############################################################################

check_database_connections() {
    log "Checking database connections..."

    local critical=0

    # Check MySQL (ATS Pro)
    if docker exec ats-mysql mysql -u root -p${MYSQL_ROOT_PASSWORD:-root} -e "SELECT 1" &>/dev/null; then
        local connections=$(docker exec ats-mysql mysql -u root -p${MYSQL_ROOT_PASSWORD:-root} -e "SHOW STATUS LIKE 'Threads_connected'" 2>/dev/null | grep Threads | awk '{print $2}')
        log "  ✓ MySQL (ATS): Connected ($connections active connections)"
    else
        send_alert "CRITICAL" "Cannot connect to MySQL (ATS Pro)"
        critical=1
    fi

    # Check PostgreSQL (Portfolio)
    if docker exec techflow-postgres pg_isready -U techflow &>/dev/null; then
        log "  ✓ PostgreSQL (Portfolio): Connected"
    else
        send_alert "CRITICAL" "Cannot connect to PostgreSQL (Portfolio)"
        critical=1
    fi

    # Check Redis instances
    for redis in "ats-redis" "techflow-redis-portfolio"; do
        if docker exec "$redis" redis-cli ping &>/dev/null | grep -q "PONG"; then
            log "  ✓ Redis ($redis): Connected"
        else
            send_alert "CRITICAL" "Cannot connect to Redis ($redis)"
            critical=1
        fi
    done

    # Check host Redis (DevFlow Pro)
    if redis-cli ping &>/dev/null | grep -q "PONG"; then
        log "  ✓ Redis (host): Connected"
    else
        send_alert "WARNING" "Cannot connect to host Redis"
        critical=1
    fi

    return $critical
}

###############################################################################
# Application Health Checks
###############################################################################

check_application_endpoints() {
    log "Checking application endpoints..."

    local critical=0
    local endpoints=(
        "http://localhost:8000|DevFlow Pro"
        "http://localhost:8080|ATS Pro"
        "http://localhost:8081|Portfolio"
    )

    for endpoint_info in "${endpoints[@]}"; do
        IFS='|' read -r url name <<< "$endpoint_info"

        local http_code=$(curl -s -o /dev/null -w "%{http_code}" "$url" --max-time 10 || echo "000")

        if [ "$http_code" = "200" ] || [ "$http_code" = "302" ]; then
            log "  ✓ $name ($url): HTTP $http_code"
        else
            send_alert "WARNING" "$name endpoint is not responding correctly (HTTP $http_code)"
            critical=1
        fi
    done

    return $critical
}

###############################################################################
# Log File Monitoring
###############################################################################

check_error_logs() {
    log "Checking for recent errors in application logs..."

    local critical=0
    local log_files=(
        "/var/www/devflow-pro/storage/logs/laravel.log"
        "/var/www/ats-pro/storage/logs/laravel.log"
    )

    for log_file in "${log_files[@]}"; do
        if [ -f "$log_file" ]; then
            local error_count=$(grep -c "ERROR" "$log_file" 2>/dev/null | tail -100 || echo 0)
            local critical_count=$(grep -c "CRITICAL" "$log_file" 2>/dev/null | tail -100 || echo 0)

            if [ "$critical_count" -gt 0 ]; then
                send_alert "WARNING" "Found $critical_count CRITICAL errors in $log_file (last 100 lines)"
                critical=1
            elif [ "$error_count" -gt 10 ]; then
                send_alert "WARNING" "Found $error_count errors in $log_file (last 100 lines)"
            else
                log "  ✓ $log_file: $error_count errors, $critical_count critical"
            fi
        fi
    done

    return $critical
}

###############################################################################
# SSL Certificate Expiration
###############################################################################

check_ssl_certificates() {
    log "Checking SSL certificate expiration..."

    local critical=0
    local domains=(
        "devflow.techflow.digital"
        "ats.techflow.digital"
        "portfolio.techflow.digital"
    )

    for domain in "${domains[@]}"; do
        local expiry_date=$(echo | openssl s_client -servername "$domain" -connect "$domain:443" 2>/dev/null | openssl x509 -noout -enddate 2>/dev/null | cut -d= -f2)

        if [ -n "$expiry_date" ]; then
            local expiry_epoch=$(date -d "$expiry_date" +%s)
            local current_epoch=$(date +%s)
            local days_until_expiry=$(( ($expiry_epoch - $current_epoch) / 86400 ))

            if [ "$days_until_expiry" -lt 7 ]; then
                send_alert "CRITICAL" "SSL certificate for $domain expires in $days_until_expiry days"
                critical=1
            elif [ "$days_until_expiry" -lt 30 ]; then
                send_alert "WARNING" "SSL certificate for $domain expires in $days_until_expiry days"
            else
                log "  ✓ SSL certificate for $domain: valid for $days_until_expiry days"
            fi
        else
            log "  ⚠ Could not check SSL certificate for $domain"
        fi
    done

    return $critical
}

###############################################################################
# Main Execution
###############################################################################

main() {
    log "=========================================="
    log "Starting system health check..."
    log "=========================================="

    local exit_code=0

    # Run all checks
    check_disk_space || exit_code=1
    check_cpu_usage || exit_code=1
    check_memory_usage || exit_code=1
    check_docker_containers || exit_code=1
    check_database_connections || exit_code=1
    check_application_endpoints || exit_code=1
    check_error_logs || exit_code=1
    check_ssl_certificates || exit_code=1

    if [ $exit_code -eq 0 ]; then
        log "=========================================="
        log "✓ All health checks passed"
        log "=========================================="
    else
        log "=========================================="
        log "⚠ Some health checks failed - review logs"
        log "=========================================="
    fi

    return $exit_code
}

# Run main function
main

exit $?
