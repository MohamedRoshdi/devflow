# PostgreSQL Testing Setup

This document explains how to use PostgreSQL for testing instead of SQLite in-memory database.

## Overview

The project has been configured to use PostgreSQL for testing to resolve SQLite cascade/transaction errors. This provides a more production-like testing environment and better handles complex database operations.

## Configuration Files

### Modified Files

1. **phpunit.xml** - Updated to use PostgreSQL connection
   - `DB_CONNECTION`: Changed from `sqlite` to `pgsql_testing`
   - Database connection on port `5433` to avoid conflicts

2. **.env.testing** - Environment variables for testing
   - PostgreSQL connection details
   - Test database: `devflow_test`
   - Test user: `devflow_test`

3. **config/database.php** - Added PostgreSQL connections
   - `pgsql`: General PostgreSQL connection (port 5432)
   - `pgsql_testing`: Dedicated testing connection (port 5433)

### New Files

1. **docker-compose.testing.yml** - Docker setup for test database
   - PostgreSQL 16 Alpine container
   - Isolated test network
   - Health checks enabled
   - Optional Redis container for cache tests

2. **run-tests.sh** - Helper script for managing tests
   - Database lifecycle management
   - Test execution shortcuts
   - Database shell access

## Quick Start

### 1. Start the Test Database

```bash
./run-tests.sh start
```

This will:
- Start PostgreSQL container on port 5433
- Wait for database to be ready
- Confirm when ready to run tests

### 2. Run Migrations

```bash
./run-tests.sh migrate
```

Or reset the database (drop, recreate, and migrate):

```bash
./run-tests.sh reset
```

### 3. Run Tests

Run all tests:
```bash
./run-tests.sh test
```

Run specific test suites:
```bash
./run-tests.sh unit        # Run unit tests only
./run-tests.sh feature     # Run feature tests only
./run-tests.sh browser     # Run browser tests only
./run-tests.sh performance # Run performance tests only
./run-tests.sh security    # Run security tests only
```

## Available Commands

### Database Management

```bash
./run-tests.sh start      # Start PostgreSQL container
./run-tests.sh stop       # Stop PostgreSQL container
./run-tests.sh restart    # Restart PostgreSQL container
./run-tests.sh reset      # Drop, recreate, and migrate database
./run-tests.sh migrate    # Run migrations only
./run-tests.sh status     # Show database status
./run-tests.sh cleanup    # Stop database and remove all volumes
```

### Testing

```bash
./run-tests.sh test [suite]  # Run all tests or specific suite
./run-tests.sh unit          # Run unit tests
./run-tests.sh feature       # Run feature tests
./run-tests.sh browser       # Run browser tests
./run-tests.sh performance   # Run performance tests
./run-tests.sh security      # Run security tests
```

### Debugging

```bash
./run-tests.sh logs       # Show PostgreSQL logs (live tail)
./run-tests.sh shell      # Open PostgreSQL shell (psql)
```

## Manual Test Execution

If you prefer to run tests manually with PHPUnit:

```bash
# Make sure database is running
./run-tests.sh start

# Run PHPUnit directly
vendor/bin/phpunit

# Or specific suites
vendor/bin/phpunit --testsuite=Unit
vendor/bin/phpunit --testsuite=Feature
vendor/bin/phpunit --filter=ProjectTest
```

## Database Connection Details

### Test Database
- **Host**: 127.0.0.1
- **Port**: 5433 (mapped from container's 5432)
- **Database**: devflow_test
- **Username**: devflow_test
- **Password**: devflow_test_password

### Direct Connection

Connect using psql:
```bash
psql -h 127.0.0.1 -p 5433 -U devflow_test -d devflow_test
# Password: devflow_test_password
```

Or use the helper script:
```bash
./run-tests.sh shell
```

## Docker Compose Details

The `docker-compose.testing.yml` file defines:

- **postgres_test**: PostgreSQL 16 Alpine container
  - Lightweight and fast
  - UTF8 encoding
  - Health checks every 10 seconds
  - Persistent volume for data

- **redis_test** (optional): Redis 7 Alpine
  - For cache/queue testing if needed
  - Port 6380 (to avoid conflicts)

## Advantages Over SQLite

1. **Better Transaction Handling**: PostgreSQL properly handles nested transactions and savepoints
2. **Cascade Operations**: Foreign key cascades work correctly
3. **Production Parity**: Testing environment closer to production
4. **Concurrent Testing**: Better support for parallel test execution
5. **Advanced Features**: Full support for JSON, arrays, and PostgreSQL-specific features

## Troubleshooting

### Database Won't Start

Check Docker is running:
```bash
docker info
```

Check container logs:
```bash
./run-tests.sh logs
```

### Connection Refused

Ensure port 5433 is not in use:
```bash
lsof -i :5433
```

Restart the database:
```bash
./run-tests.sh restart
```

### Migration Errors

Reset the database completely:
```bash
./run-tests.sh reset
```

### Clean Start

Remove all test data and start fresh:
```bash
./run-tests.sh cleanup
./run-tests.sh start
./run-tests.sh migrate
```

## CI/CD Integration

For CI/CD pipelines, you can use the following pattern:

```yaml
# Example GitHub Actions workflow
- name: Start Test Database
  run: docker-compose -f docker-compose.testing.yml up -d

- name: Wait for PostgreSQL
  run: |
    timeout 30 bash -c 'until docker-compose -f docker-compose.testing.yml exec -T postgres_test pg_isready -U devflow_test; do sleep 1; done'

- name: Run Migrations
  run: php artisan migrate --env=testing --force

- name: Run Tests
  run: vendor/bin/phpunit

- name: Cleanup
  if: always()
  run: docker-compose -f docker-compose.testing.yml down -v
```

## Performance Notes

- **Initial Setup**: First migration may take 30-60 seconds
- **Test Execution**: Similar or faster than SQLite for complex tests
- **Database Reset**: Use `./run-tests.sh reset` between test runs if needed
- **Parallel Testing**: PostgreSQL supports Laravel Parallel Testing better than SQLite

## Switching Back to SQLite

If you need to switch back to SQLite for any reason:

1. Edit `phpunit.xml`:
   ```xml
   <env name="DB_CONNECTION" value="sqlite"/>
   <env name="DB_DATABASE" value=":memory:"/>
   ```

2. Edit `.env.testing`:
   ```
   DB_CONNECTION=sqlite
   DB_DATABASE=:memory:
   ```

## Additional Resources

- [Laravel Database Testing](https://laravel.com/docs/database-testing)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)

## Support

For issues or questions about the testing setup, refer to:
- Project documentation: `TESTING.md`
- Laravel testing guide: `https://laravel.com/docs/testing`
