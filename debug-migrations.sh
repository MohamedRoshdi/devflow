#!/bin/bash
# Debug MySQL migrations to find problematic files

set -e

LOG_FILE="/tmp/migration-debug-$(date +%Y%m%d_%H%M%S).log"
SLOW_THRESHOLD=5.0  # Flag migrations taking >5 seconds

echo "=== MySQL Migration Debugging Tool ===" | tee -a "$LOG_FILE"
echo "Started: $(date)" | tee -a "$LOG_FILE"
echo "Log file: $LOG_FILE"
echo ""

# Ensure MySQL is ready
echo "Checking MySQL connection..."
if ! docker exec devflow_mysql_test mysql -u devflow_test -pdevflow_test_password -e "SELECT 1" &>/dev/null; then
    echo "❌ MySQL is not accessible"
    exit 1
fi
echo "✅ MySQL is ready"
echo ""

# Function to reset database
reset_database() {
    echo "Resetting database..." | tee -a "$LOG_FILE"

    # Drop all tables
    docker exec devflow_mysql_test mysql -u devflow_test -pdevflow_test_password devflow_test -e "
    SET FOREIGN_KEY_CHECKS=0;
    SET GROUP_CONCAT_MAX_LEN=32768;
    SET @tables = NULL;
    SELECT GROUP_CONCAT('\`', table_name, '\`') INTO @tables
    FROM information_schema.tables
    WHERE table_schema = 'devflow_test';

    SET @tables = CONCAT('DROP TABLE IF EXISTS ', @tables);
    PREPARE stmt FROM @tables;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
    SET FOREIGN_KEY_CHECKS=1;
    " 2>/dev/null

    # Create migrations table
    php artisan migrate:install --database=mysql 2>&1 | grep -v "Nothing" || true
}

# Reset database initially
reset_database
echo ""

# Get list of migration files
migrations=($(ls -1 database/migrations/*.php | sort))
total=${#migrations[@]}
current=0
failed_migrations=()
slow_migrations=()
total_time=0

echo "Found $total migration files to test" | tee -a "$LOG_FILE"
echo "Slow threshold: ${SLOW_THRESHOLD}s" | tee -a "$LOG_FILE"
echo "" | tee -a "$LOG_FILE"

printf "%-60s | %-10s | %-8s\n" "Migration" "Duration" "Status" | tee -a "$LOG_FILE"
printf "%-60s-+-%-10s-+-%-8s\n" "------------------------------------------------------------" "----------" "--------" | tee -a "$LOG_FILE"

for migration in "${migrations[@]}"; do
    ((current++))
    filename=$(basename "$migration")

    # Run migration with timeout
    start=$(date +%s.%N)

    if timeout 30 php artisan migrate --path="database/migrations/$filename" --database=mysql --force &>/tmp/migrate_output.txt; then
        end=$(date +%s.%N)
        duration=$(echo "$end - $start" | bc)
        total_time=$(echo "$total_time + $duration" | bc)

        # Check if migration actually ran
        if grep -q "Nothing to migrate" /tmp/migrate_output.txt; then
            status="⊘ Skip"
        elif grep -q "Migrated" /tmp/migrate_output.txt; then
            status="✓ Pass"

            # Check if slow
            if (( $(echo "$duration > $SLOW_THRESHOLD" | bc -l) )); then
                status="⚠ Slow"
                slow_migrations+=("$filename:$duration")
            fi
        else
            status="? Unknown"
        fi

        printf "%-60s | %8.2fs | %-8s\n" "$filename" "$duration" "$status" | tee -a "$LOG_FILE"
    else
        end=$(date +%s.%N)
        duration=$(echo "$end - $start" | bc)
        status="✗ FAIL"
        failed_migrations+=("$filename")

        printf "%-60s | %8.2fs | %-8s\n" "$filename" "$duration" "$status" | tee -a "$LOG_FILE"

        # Capture error details
        echo "  Error output:" | tee -a "$LOG_FILE"
        cat /tmp/migrate_output.txt | head -10 | sed 's/^/    /' | tee -a "$LOG_FILE"

        echo "" | tee -a "$LOG_FILE"
        echo "⚠️  FAILED MIGRATION DETECTED: $filename" | tee -a "$LOG_FILE"
        echo "    Stopping to preserve database state for analysis" | tee -a "$LOG_FILE"
        break
    fi
done

echo "" | tee -a "$LOG_FILE"
echo "===================================================" | tee -a "$LOG_FILE"
echo "Migration Testing Complete" | tee -a "$LOG_FILE"
echo "===================================================" | tee -a "$LOG_FILE"
echo "Tested: $current / $total migrations" | tee -a "$LOG_FILE"
echo "Total time: ${total_time}s" | tee -a "$LOG_FILE"
echo ""

if [ ${#failed_migrations[@]} -gt 0 ]; then
    echo "❌ FAILED MIGRATIONS (${#failed_migrations[@]}):" | tee -a "$LOG_FILE"
    for failed in "${failed_migrations[@]}"; do
        echo "  - $failed" | tee -a "$LOG_FILE"
    done
    echo ""
fi

if [ ${#slow_migrations[@]} -gt 0 ]; then
    echo "⚠️  SLOW MIGRATIONS (${#slow_migrations[@]}):" | tee -a "$LOG_FILE"
    for slow in "${slow_migrations[@]}"; do
        IFS=':' read -r name time <<< "$slow"
        echo "  - $name (${time}s)" | tee -a "$LOG_FILE"
    done
    echo ""
fi

if [ ${#failed_migrations[@]} -eq 0 ] && [ $current -eq $total ]; then
    echo "✅ All migrations passed!" | tee -a "$LOG_FILE"
    echo ""
    echo "Average time per migration: $(echo "scale=2; $total_time / $total" | bc)s" | tee -a "$LOG_FILE"
fi

echo "Completed: $(date)" | tee -a "$LOG_FILE"
echo "Full log saved to: $LOG_FILE"
