#!/bin/bash
# One-time MySQL test database setup
# Run this BEFORE executing tests

set -e

echo "════════════════════════════════════════════════════════════════"
echo "  MySQL Test Database Setup"
echo "════════════════════════════════════════════════════════════════"
echo ""

# Check if MySQL container is running
if ! docker ps | grep -q devflow_mysql_test; then
    echo "❌ MySQL test container is not running!"
    echo "   Start it with: docker-compose -f docker-compose.testing.yml up -d mysql_test"
    exit 1
fi

echo "✓ MySQL container is running"

# Set environment variables
export DB_HOST=127.0.0.1
export DB_PORT=3308
export DB_DATABASE=devflow_test
export DB_USERNAME=devflow_test
export DB_PASSWORD=devflow_test_password
export APP_ENV=testing

echo "✓ Environment variables set"
echo ""

# Test connection
echo "Testing MySQL connection..."
if docker exec devflow_mysql_test mysql -u devflow_test -pdevflow_test_password -e "SELECT 1" &>/dev/null; then
    echo "✓ MySQL connection successful"
else
    echo "❌ Cannot connect to MySQL"
    exit 1
fi
echo ""

# Drop all tables to start fresh
echo "Clearing existing tables..."
docker exec devflow_mysql_test mysql -u devflow_test -pdevflow_test_password devflow_test <<EOF 2>/dev/null
SET FOREIGN_KEY_CHECKS=0;
SELECT CONCAT('DROP TABLE IF EXISTS \`', table_name, '\`;')
FROM information_schema.tables
WHERE table_schema = 'devflow_test'
INTO @dropstatement;

SET @dropstatement = IFNULL(@dropstatement, 'SELECT 1');
PREPARE stmt FROM @dropstatement;
-- EXECUTE stmt;
-- DEALLOCATE PREPARE stmt;
SET FOREIGN_KEY_CHECKS=1;
EOF

# Actually drop tables one by one to avoid issues
tables=$(docker exec devflow_mysql_test mysql -u devflow_test -pdevflow_test_password devflow_test -sN \
  -e "SELECT table_name FROM information_schema.tables WHERE table_schema='devflow_test'" 2>/dev/null)

if [ ! -z "$tables" ]; then
    docker exec devflow_mysql_test mysql -u devflow_test -pdevflow_test_password devflow_test \
      -e "SET FOREIGN_KEY_CHECKS=0; $(echo "$tables" | sed 's/^/DROP TABLE IF EXISTS `/;s/$/`;/' | tr '\n' ' ') SET FOREIGN_KEY_CHECKS=1;" 2>/dev/null
    echo "✓ Existing tables dropped"
else
    echo "✓ No existing tables to drop"
fi
echo ""

# Run migrations
echo "Running migrations..."
start_time=$(date +%s)

if php artisan migrate --database=mysql --force 2>&1 | grep -E "DONE|FAIL"; then
    end_time=$(date +%s)
    duration=$((end_time - start_time))

    # Check if all migrations completed
    migration_count=$(docker exec devflow_mysql_test mysql -u devflow_test -pdevflow_test_password devflow_test \
      -sN -e "SELECT COUNT(*) FROM migrations" 2>/dev/null)

    table_count=$(docker exec devflow_mysql_test mysql -u devflow_test -pdevflow_test_password devflow_test \
      -sN -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='devflow_test'" 2>/dev/null)

    echo ""
    echo "✓ Migrations completed in ${duration}s"
    echo "  → $migration_count migrations applied"
    echo "  → $table_count tables created"
else
    echo ""
    echo "❌ Migration failed!"
    exit 1
fi

echo ""
echo "════════════════════════════════════════════════════════════════"
echo "  ✅ Test database ready!"
echo "════════════════════════════════════════════════════════════════"
echo ""
echo "You can now run tests:"
echo "  php artisan test"
echo "  php artisan test --testsuite=Security"
echo "  php artisan test --testsuite=Feature"
echo ""
echo "Note: If migrations change, re-run this script."
echo "════════════════════════════════════════════════════════════════"
