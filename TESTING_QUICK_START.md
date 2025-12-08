# Testing Quick Start Guide

## First Time Setup

1. **Start the test database:**
   ```bash
   ./run-tests.sh start
   ```
   or
   ```bash
   make test-db-start
   ```

2. **Run migrations:**
   ```bash
   ./run-tests.sh migrate
   ```
   or
   ```bash
   make test-db-reset
   ```

3. **Run tests:**
   ```bash
   ./run-tests.sh test
   ```
   or
   ```bash
   make test
   ```

## Daily Workflow

### Using the Shell Script

```bash
# Start database (if not running)
./run-tests.sh start

# Run all tests
./run-tests.sh test

# Run specific suite
./run-tests.sh unit
./run-tests.sh feature
./run-tests.sh browser

# Stop database when done
./run-tests.sh stop
```

### Using Makefile

```bash
# Complete workflow (start DB, reset, run tests)
make test-all

# Individual suites
make test-unit
make test-feature
make test-browser

# With coverage
make test-coverage

# Stop database
make test-db-stop
```

## Common Tasks

### Reset Database
```bash
./run-tests.sh reset
# or
make test-db-reset
```

### Debug Failed Tests
```bash
# Check database status
./run-tests.sh status

# View logs
./run-tests.sh logs

# Access database directly
./run-tests.sh shell
```

### Clean Everything
```bash
./run-tests.sh cleanup
```

## Why PostgreSQL?

- Fixes SQLite cascade/transaction errors
- Better production environment parity
- Proper foreign key constraint handling
- Support for concurrent testing
- 3044 unit tests now run reliably

## Port Information

- PostgreSQL Test Database: `5433`
- Redis Test (optional): `6380`

These ports avoid conflicts with production databases running on default ports.

## Need Help?

See full documentation: `TESTING_POSTGRESQL.md`
