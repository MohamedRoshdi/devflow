#!/bin/bash
# Setup test database before running tests

echo "Setting up MySQL test database..."

# Set environment variables
export DB_HOST=127.0.0.1
export DB_PORT=3308
export DB_DATABASE=devflow_test
export DB_USERNAME=devflow_test
export DB_PASSWORD=devflow_test_password
export APP_ENV=testing

# Run fresh migrations
php artisan migrate:fresh --database=mysql --force

if [ $? -eq 0 ]; then
    echo "✅ Test database ready! ($(php artisan db:show --database=mysql | grep Tables | awk '{print $2}') tables created)"
    echo ""
    echo "You can now run tests:"
    echo "  php artisan test"
    echo "  php artisan test --testsuite=Security"
else
    echo "❌ Failed to setup test database"
    exit 1
fi
