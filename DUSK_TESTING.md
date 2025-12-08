# Laravel Dusk Browser Testing Guide

This guide explains how to run Laravel Dusk browser tests for DevFlow Pro using Docker and Selenium.

## Overview

The Dusk testing setup uses Docker containers to provide a consistent, portable testing environment:

- **Selenium Chrome Standalone**: Runs browser tests in a containerized Chrome instance
- **MySQL Database**: Isolated test database
- **PHP Application**: Your Laravel application served via PHP's built-in server
- **VNC Viewer**: Watch tests run in real-time (optional)

## Architecture

```
┌─────────────────────────────────────────────────────────┐
│                   Docker Network (dusk-network)         │
│                                                          │
│  ┌──────────────┐    ┌──────────────┐   ┌───────────┐  │
│  │   App        │◄───┤   Selenium   │◄──┤   Tests   │  │
│  │ (Laravel)    │    │   Chrome     │   │  (PHPUnit)│  │
│  │ Port: 9000   │    │ Port: 4444   │   │           │  │
│  └──────┬───────┘    └──────────────┘   └───────────┘  │
│         │                                                │
│         │                                                │
│  ┌──────▼───────┐                                       │
│  │   MySQL      │                                       │
│  │ Port: 3306   │                                       │
│  └──────────────┘                                       │
└─────────────────────────────────────────────────────────┘
```

## Prerequisites

- Docker installed and running
- Docker Compose installed
- At least 4GB of available RAM

## Quick Start

### 1. Run All Tests

```bash
./run-dusk-tests.sh
```

This will:
1. Start all Docker containers (MySQL, Selenium, App)
2. Wait for all services to be ready
3. Run database migrations
4. Execute all Dusk tests
5. Display results

### 2. Run Specific Test File

```bash
./run-dusk-tests.sh --filter=AuthenticationTest
```

### 3. Run Specific Test Method

```bash
./run-dusk-tests.sh --filter=testLoginSuccessful
```

### 4. Run Tests with Stop on Failure

```bash
./run-dusk-tests.sh --stop-on-failure
```

### 5. Run Tests from a Group

```bash
./run-dusk-tests.sh --group=authentication
```

### 6. Combine Multiple Options

```bash
./run-dusk-tests.sh --filter=Project --stop-on-failure
```

## Viewing Tests in Real-Time

The Selenium container includes a VNC server that allows you to watch tests as they run:

1. Open your web browser
2. Navigate to: **http://localhost:7900**
3. Enter password: **secret**
4. Watch your tests execute in real-time!

Alternatively, connect using a VNC client to `localhost:7900`.

## Manual Docker Commands

If you prefer to run commands manually:

### Start Containers

```bash
# Docker Compose v2 (recommended)
docker compose -f docker-compose.dusk.yml up -d

# Docker Compose v1 (legacy)
docker-compose -f docker-compose.dusk.yml up -d
```

### Run Migrations

```bash
docker-compose -f docker-compose.dusk.yml exec app php artisan migrate:fresh --seed
```

### Run All Tests

```bash
docker-compose -f docker-compose.dusk.yml exec app php artisan dusk
```

### Run Specific Test

```bash
docker-compose -f docker-compose.dusk.yml exec app php artisan dusk --filter=YourTestName
```

### View Logs

```bash
# Application logs
docker-compose -f docker-compose.dusk.yml logs app

# Selenium logs
docker-compose -f docker-compose.dusk.yml logs selenium

# MySQL logs
docker-compose -f docker-compose.dusk.yml logs mysql
```

### Stop Containers

```bash
docker-compose -f docker-compose.dusk.yml down
```

### Stop Containers and Remove Volumes

```bash
docker-compose -f docker-compose.dusk.yml down -v
```

## File Structure

```
devflow/
├── docker-compose.dusk.yml      # Docker Compose configuration for Dusk
├── Dockerfile.dusk              # Dockerfile for the test application
├── .env.dusk.docker             # Environment variables for Docker testing
├── run-dusk-tests.sh            # Convenient test runner script
├── phpunit.dusk.xml             # PHPUnit configuration for Dusk
├── tests/
│   ├── DuskTestCase.php         # Base test case for Dusk tests
│   └── Browser/                 # All Dusk browser tests
│       ├── screenshots/         # Failed test screenshots
│       ├── console/             # Browser console logs
│       └── *.php                # Your test files
```

## Configuration Files

### docker-compose.dusk.yml

Defines three services:
- **app**: Laravel application container
- **selenium**: Chrome browser for testing
- **mysql**: Test database

### Dockerfile.dusk

Builds a PHP 8.4 image with all necessary extensions and Composer dependencies.

### .env.dusk.docker

Environment configuration optimized for Docker testing:
- Uses `mysql` as DB host (container name)
- Uses `selenium` as Selenium host (container name)
- Configured for testing environment

### DuskTestCase.php

Updated to:
- Detect Docker environment automatically
- Connect to Selenium container instead of local ChromeDriver
- Use appropriate Chrome options for Docker

## Environment Variables

Key environment variables in `.env.dusk.docker`:

```env
APP_URL=http://app:9000              # Application URL (container network)
DB_HOST=mysql                        # MySQL container hostname
DUSK_DRIVER_URL=http://selenium:4444 # Selenium WebDriver URL
```

## Troubleshooting

### Tests Can't Connect to Selenium

**Symptoms**: Connection refused errors, timeout errors

**Solution**:
```bash
# Check if Selenium is running
docker-compose -f docker-compose.dusk.yml ps selenium

# Check Selenium logs
docker-compose -f docker-compose.dusk.yml logs selenium

# Verify Selenium is ready
curl http://localhost:4444/wd/hub/status
```

### Tests Can't Connect to Application

**Symptoms**: Application not found, connection errors

**Solution**:
```bash
# Check if app container is running
docker-compose -f docker-compose.dusk.yml ps app

# Check app logs
docker-compose -f docker-compose.dusk.yml logs app

# Verify app is accessible
curl http://localhost:9000
```

### Database Connection Issues

**Symptoms**: Database connection errors, migration failures

**Solution**:
```bash
# Check MySQL status
docker-compose -f docker-compose.dusk.yml ps mysql

# Check MySQL logs
docker-compose -f docker-compose.dusk.yml logs mysql

# Try connecting manually
docker-compose -f docker-compose.dusk.yml exec mysql mysql -u devflow -psecret devflow_dusk
```

### Chrome Crashes or Hangs

**Symptoms**: Tests timeout, Chrome process crashes

**Solution**:
```bash
# Increase shared memory (in docker-compose.dusk.yml)
shm_size: 2gb  # Already configured

# Add more Chrome arguments in DuskTestCase.php
'--disable-dev-shm-usage'  # Already added
'--no-sandbox'             # Already added
```

### Port Already in Use

**Symptoms**: "Port is already allocated" error

**Solution**:
```bash
# Check what's using the ports
lsof -i :4444  # Selenium
lsof -i :9000  # Application
lsof -i :7900  # VNC

# Stop conflicting services or change ports in docker-compose.dusk.yml
```

### Permission Issues

**Symptoms**: Can't write screenshots, can't create files

**Solution**:
```bash
# Fix permissions on host
chmod -R 777 tests/Browser/screenshots
chmod -R 777 tests/Browser/console
chmod -R 777 storage

# Or run in container
docker-compose -f docker-compose.dusk.yml exec app chmod -R 777 tests/Browser
```

### Out of Memory

**Symptoms**: Container crashes, out of memory errors

**Solution**:
```bash
# Increase Docker memory limit in Docker Desktop settings
# Recommended: At least 4GB for Dusk tests

# Or reduce parallel test execution
SE_NODE_MAX_SESSIONS=1  # In docker-compose.dusk.yml
```

## Best Practices

### 1. Clean Database Between Tests

Always use database transactions or refresh the database:

```php
use Illuminate\Foundation\Testing\DatabaseMigrations;

class YourTest extends DuskTestCase
{
    use DatabaseMigrations;

    // Your tests...
}
```

### 2. Wait for Elements

Always wait for elements to be present:

```php
$browser->waitFor('.element-class')
    ->click('.element-class');
```

### 3. Use Descriptive Selectors

Use data attributes for reliable element selection:

```html
<button data-dusk="login-button">Login</button>
```

```php
$browser->click('@login-button');
```

### 4. Take Screenshots on Failure

Dusk automatically takes screenshots on failure. They're saved in:
```
tests/Browser/screenshots/
```

### 5. Check Console Logs

Browser console logs are saved in:
```
tests/Browser/console/
```

### 6. Run Tests in Groups

Organize tests with groups for faster feedback:

```php
/**
 * @group authentication
 * @group critical
 */
class AuthenticationTest extends DuskTestCase
{
    // ...
}
```

Run specific groups:
```bash
./run-dusk-tests.sh --group=critical
```

## CI/CD Integration

### GitHub Actions Example

```yaml
name: Dusk Tests

on: [push, pull_request]

jobs:
  dusk:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v2

      - name: Run Dusk Tests
        run: |
          chmod +x run-dusk-tests.sh
          ./run-dusk-tests.sh --stop-on-failure

      - name: Upload Screenshots
        if: failure()
        uses: actions/upload-artifact@v3
        with:
          name: dusk-screenshots
          path: tests/Browser/screenshots/
```

### GitLab CI Example

```yaml
dusk:
  image: docker:latest
  services:
    - docker:dind
  script:
    - chmod +x run-dusk-tests.sh
    - ./run-dusk-tests.sh --stop-on-failure
  artifacts:
    when: on_failure
    paths:
      - tests/Browser/screenshots/
      - tests/Browser/console/
```

## Performance Tips

### 1. Use Fewer Database Migrations

Instead of `DatabaseMigrations`, consider:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
```

### 2. Parallelize Tests

Split tests across multiple containers (advanced):

```yaml
# Run tests in parallel (requires additional setup)
services:
  selenium-1:
    image: selenium/standalone-chrome:latest
  selenium-2:
    image: selenium/standalone-chrome:latest
```

### 3. Reduce Wait Times

Use explicit waits instead of arbitrary sleeps:

```php
// Good
$browser->waitFor('.element', 5);

// Bad
sleep(5);
$browser->click('.element');
```

### 4. Use Fast Database

SQLite in memory is faster for simple tests:

```env
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

## Selenium Grid (Advanced)

For running tests across multiple browsers:

```yaml
# docker-compose.dusk.grid.yml
services:
  selenium-hub:
    image: selenium/hub:latest

  chrome:
    image: selenium/node-chrome:latest
    environment:
      - SE_EVENT_BUS_HOST=selenium-hub

  firefox:
    image: selenium/node-firefox:latest
    environment:
      - SE_EVENT_BUS_HOST=selenium-hub
```

## Additional Resources

- [Laravel Dusk Documentation](https://laravel.com/docs/dusk)
- [Selenium Documentation](https://www.selenium.dev/documentation/)
- [Docker Documentation](https://docs.docker.com/)
- [Chrome DevTools Protocol](https://chromedevtools.github.io/devtools-protocol/)

## Support

For issues specific to DevFlow Pro:
- Check the test output and logs
- Review screenshots in `tests/Browser/screenshots/`
- Check browser console logs in `tests/Browser/console/`
- Ensure all Docker containers are running properly

## Summary

You now have a complete Docker-based Dusk testing setup that:

- ✅ Runs tests in isolated containers
- ✅ Provides consistent results across environments
- ✅ Allows real-time viewing via VNC
- ✅ Includes convenient test runner script
- ✅ Works with CI/CD pipelines
- ✅ Supports all Dusk testing features

Happy testing!
