#!/bin/bash
# Profile MySQL migrations to identify slow ones

echo "=== MySQL Migration Performance Profile ==="
echo "Database: devflow_test"
echo "Started: $(date)"
echo ""

# Fresh start
echo "Dropping all tables..."
docker exec -i devflow_mysql_test mysql -u devflow_test -pdevflow_test_password devflow_test -e "
SET FOREIGN_KEY_CHECKS=0;
SELECT CONCAT('DROP TABLE IF EXISTS \`', table_name, '\`;')
FROM information_schema.tables
WHERE table_schema = 'devflow_test' AND table_name != 'migrations';
SET FOREIGN_KEY_CHECKS=1;
" 2>/dev/null | grep "DROP TABLE" | docker exec -i devflow_mysql_test mysql -u devflow_test -pdevflow_test_password devflow_test 2>/dev/null

# Create migrations table
php artisan migrate:install --database=mysql 2>&1 | grep -v "Nothing to migrate"

echo "| Migration File | Duration (seconds) | Status |"
echo "|----------------|-------------------|--------|"

# Get list of migrations
migrations=$(ls -1 database/migrations/*.php)
total=0

for migration in $migrations; do
    filename=$(basename "$migration")

    # Run single migration and time it
    start=$(date +%s.%N)
    output=$(php artisan migrate --path="database/migrations/$filename" --database=mysql --force 2>&1)
    end=$(date +%s.%N)

    duration=$(echo "$end - $start" | bc)

    # Check if migration actually ran or was skipped
    if echo "$output" | grep -q "Nothing to migrate"; then
        status="Skipped"
    elif echo "$output" | grep -q "Migrated"; then
        status="✓"
        total=$(echo "$total + $duration" | bc)
    else
        status="✗ Error"
    fi

    printf "| %-40s | %8.2f | %-8s |\n" "$filename" "$duration" "$status"

    # Flag slow migrations (>2 seconds)
    if (( $(echo "$duration > 2.0" | bc -l) )); then
        echo "  ⚠️  SLOW MIGRATION DETECTED"
    fi
done

echo ""
echo "Total migration time: $total seconds"
echo "Completed: $(date)"
