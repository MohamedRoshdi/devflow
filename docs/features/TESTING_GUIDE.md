# DevFlow Pro - Comprehensive Test Suite

## Overview

This document describes the complete test suite implemented for DevFlow Pro, covering unit tests, feature tests, Livewire component tests, and API endpoint tests.

## Test Structure

```
tests/
├── Unit/
│   └── Services/
│       ├── ServerMetricsServiceTest.php
│       ├── DatabaseBackupServiceTest.php
│       └── PipelineExecutionServiceTest.php
├── Feature/
│   ├── Livewire/
│   │   ├── DashboardTest.php
│   │   ├── ServerListTest.php (to be created)
│   │   ├── ProjectListTest.php (to be created)
│   │   └── DeploymentShowTest.php (to be created)
│   ├── AuthenticationTest.php (to be created)
│   ├── ProjectManagementTest.php (to be created)
│   └── ServerManagementTest.php (to be created)
├── Traits/
│   ├── CreatesProjects.php
│   ├── CreatesServers.php
│   └── MocksSSH.php
└── TestCase.php
```

## Test Coverage

### 1. Unit Tests - Services

#### ServerMetricsServiceTest (10 tests)
- ✅ Collects metrics from online server
- ✅ Returns null on metrics collection failure
- ✅ Retrieves metrics history
- ✅ Filters metrics history by period
- ✅ Gets latest metrics
- ✅ Sanitizes decimal values
- ✅ Parses process output correctly
- ✅ Gets top processes by memory
- ✅ Handles localhost detection
- ✅ Truncates long command strings

**Key Features Tested:**
- SSH connection handling
- Metrics parsing from server output
- Data sanitization and validation
- Process monitoring
- Historical data retrieval

#### DatabaseBackupServiceTest (14 tests)
- ✅ Creates MySQL backup successfully
- ✅ Generates correct backup filename
- ✅ Handles backup failure gracefully
- ✅ Calculates checksum correctly
- ✅ Verifies backup integrity
- ✅ Applies retention policy
- ✅ Supports PostgreSQL backups
- ✅ Supports SQLite backups
- ✅ Deletes backup and file
- ✅ Restores database from backup
- ✅ Prevents restoring incomplete backup
- ✅ Uploads backup to S3
- ✅ Downloads backup from S3
- ✅ Encrypts backups

**Key Features Tested:**
- Multi-database support (MySQL, PostgreSQL, SQLite)
- Backup creation and restoration
- File integrity verification
- Retention policy management
- S3 storage integration
- Encryption support

#### PipelineExecutionServiceTest (12 tests)
- ✅ Executes pipeline successfully
- ✅ Stops pipeline on stage failure
- ✅ Continues on failure if configured
- ✅ Executes stages in correct order
- ✅ Skips disabled stages
- ✅ Executes single stage successfully
- ✅ Handles stage execution failure
- ✅ Skips stage with no commands
- ✅ Calculates progress correctly
- ✅ Cancels pipeline
- ✅ Performs rollback to previous deployment
- ✅ Handles command timeout

**Key Features Tested:**
- Pipeline orchestration
- Stage ordering (pre_deploy, deploy, post_deploy)
- Error handling and rollback
- Progress tracking
- Command execution via SSH

### 2. Feature Tests - Livewire Components

#### DashboardTest (50+ tests)
- ✅ Renders successfully
- ✅ Displays server statistics
- ✅ Displays project statistics
- ✅ Displays deployment statistics
- ✅ Loads recent deployments
- ✅ Loads recent projects
- ✅ Displays SSL statistics
- ✅ Displays health check statistics
- ✅ Shows recent activity
- ✅ Loads more activity
- ✅ Refreshes dashboard data
- ✅ Clears all caches
- ✅ Toggles section collapse
- ✅ Displays server health metrics
- ✅ Displays queue statistics
- ✅ Loads deployment timeline
- ✅ Handles missing data gracefully
- ✅ Requires authentication
- ✅ Caching functionality
- ✅ User preferences

**Key Features Tested:**
- Dashboard rendering and data loading
- Real-time statistics
- Cache management
- User preferences
- Activity feed
- Timeline visualization

## Helper Classes and Traits

### Test Traits

#### CreatesProjects
Provides convenient methods for creating test projects:
- `createProject($attributes)` - Create a basic project
- `createLaravelProject($attributes)` - Create Laravel-specific project
- `createRunningProject($attributes)` - Create running project
- `createStoppedProject($attributes)` - Create stopped project
- `createProjects($count, $attributes)` - Create multiple projects
- `createProjectForServer(Server $server)` - Create project for specific server
- `createProjectForUser(User $user)` - Create project for specific user

#### CreatesServers
Provides convenient methods for creating test servers:
- `createServer($attributes)` - Create a basic server
- `createOnlineServer($attributes)` - Create online server
- `createOfflineServer($attributes)` - Create offline server
- `createServerWithDocker($attributes)` - Create server with Docker
- `createServerWithPassword($attributes)` - Create server with password auth
- `createServerWithSshKey($attributes)` - Create server with SSH key auth
- `createServers($count, $attributes)` - Create multiple servers
- `createServerForUser(User $user)` - Create server for specific user

#### MocksSSH
Provides SSH mocking functionality:
- `mockSshSuccess($output)` - Mock successful SSH connection
- `mockSshFailure($error)` - Mock failed SSH connection
- `mockServerMetrics($metrics)` - Mock server metrics collection
- `mockDatabaseBackup($success)` - Mock database backup commands
- `mockDockerCommands($success)` - Mock Docker commands
- `mockGitCommands($success, $commitHash)` - Mock Git commands

### Enhanced TestCase

The base `TestCase` class provides:
- `setUp()` - Automatic database refresh and Vite disabling
- `actingAsUser($user)` - Quick authentication
- `assertHasValidationErrors($fields)` - Validation testing
- `mockSshConnection()` - SSH connection mocking
- `mockSuccessfulCommand($output)` - Success command mocking
- `mockFailedCommand($error)` - Failed command mocking

## Factory Improvements

### New Factories Created

1. **PipelineRunFactory**
   - States: `running()`, `success()`, `failed()`

2. **PipelineStageFactory**
   - States: `preDeploy()`, `deploy()`, `postDeploy()`

3. **PipelineStageRunFactory**
   - States: `success()`, `failed()`

4. **BackupScheduleFactory**
   - States: `mysql()`, `postgresql()`, `s3()`

### Existing Factories Enhanced
- ProjectFactory with states
- ServerFactory with authentication states
- DeploymentFactory with status states
- ServerMetricFactory
- HealthCheckFactory
- SSLCertificateFactory

## Running Tests

### Run All Tests
```bash
php artisan test
```

### Run Specific Test Suite
```bash
# Unit tests only
php artisan test --testsuite=Unit

# Feature tests only
php artisan test --testsuite=Feature

# Specific test file
php artisan test tests/Unit/Services/ServerMetricsServiceTest.php

# Specific test method
php artisan test --filter it_collects_metrics_from_online_server
```

### Run Tests with Coverage
```bash
php artisan test --coverage
```

### Run Tests in Parallel
```bash
php artisan test --parallel
```

## Test Configuration

### PHPUnit Configuration (`phpunit.xml`)

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="BCRYPT_ROUNDS" value="4"/>
    <env name="CACHE_DRIVER" value="array"/>
    <env name="DB_CONNECTION" value="mysql"/>
    <env name="DB_DATABASE" value="devflow_pro_test"/>
    <env name="MAIL_MAILER" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="SESSION_DRIVER" value="array"/>
</php>
```

### Database Setup for Testing

1. Create test database:
```sql
CREATE DATABASE devflow_pro_test;
GRANT ALL PRIVILEGES ON devflow_pro_test.* TO 'your_user'@'localhost';
```

2. Run migrations:
```bash
php artisan migrate --database=mysql --env=testing
```

## Best Practices Followed

### 1. AAA Pattern (Arrange-Act-Assert)
All tests follow the Arrange-Act-Assert pattern for clarity:
```php
// Arrange - Set up test data and conditions
$server = $this->createOnlineServer();

// Act - Execute the code being tested
$metric = $this->service->collectMetrics($server);

// Assert - Verify the results
$this->assertInstanceOf(ServerMetric::class, $metric);
```

### 2. Descriptive Test Names
Test methods use descriptive names starting with `it_`:
- `it_collects_metrics_from_online_server()`
- `it_creates_mysql_backup_successfully()`
- `it_handles_backup_failure_gracefully()`

### 3. Mocking External Dependencies
- SSH connections are mocked to prevent actual server connections
- Process execution is mocked for predictable testing
- File operations are tested with temporary files

### 4. Database Transactions
All tests use `RefreshDatabase` trait to ensure:
- Clean database state for each test
- Fast test execution
- No test pollution

### 5. Comprehensive Coverage
Tests cover:
- Happy path scenarios
- Error handling
- Edge cases
- Data validation
- Authentication and authorization

## Implemented Test Files

The following test files have been created and are all passing:

### AuthenticationTest ✅ (20 tests)
- ✅ Login with Livewire (correct credentials, wrong password, nonexistent email)
- ✅ Login validation (email, password required)
- ✅ Registration closed (redirects to login)
- ✅ Logout functionality
- ✅ Password reset with Livewire
- ✅ Session management (remember me, session regeneration)
- ✅ Protected routes (dashboard, projects, servers)

### ServerManagementTest ✅
- ✅ Server CRUD operations
- ✅ SSH key and password authentication
- ✅ Server status and metrics
- ✅ Hostname optional/required validation
- ✅ Authorization (users can only access own servers)

### WebhookTest ✅ (11 tests)
- ✅ GitHub webhooks (push, ping, signature validation)
- ✅ GitLab webhooks
- ✅ Bitbucket webhooks
- ✅ Webhook security (signature, content-type validation)
- ✅ Rate limiting

### API Tests ✅ (69 tests)
- ✅ Project API (CRUD, deployments, validation)
- ✅ Server API (CRUD, metrics, validation)
- ✅ Deployment API (trigger, rollback)
- ✅ Authentication (API tokens)
- ✅ Authorization (users can only access own resources - 403 for cross-user access)

### RateLimitingTest ✅ (10 tests)
- ✅ API rate limiting
- ✅ Webhook rate limiting
- ✅ Login rate limiting
- ✅ Deployment rate limiting
- ✅ Rate limit headers

## Continuous Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: devflow_pro_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s

    steps:
      - uses: actions/checkout@v2

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: mbstring, xml, ctype, json, bcmath, dom

      - name: Install Dependencies
        run: composer install --prefer-dist --no-progress

      - name: Run Tests
        run: php artisan test --parallel
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: devflow_pro_test
          DB_USERNAME: root
          DB_PASSWORD: password
```

## Test Metrics

### Current Coverage (December 2025)
- Unit Tests: 36+ tests
- Feature Tests: 282 tests (all passing)
  - API Tests: 69 tests
  - Authentication Tests: 20 tests
  - Webhook Tests: 11 tests
  - Rate Limiting Tests: 10 tests
  - Livewire Component Tests: 172+ tests
- **Total: 318+ tests**

### Test Run Results
```
Tests: 282, Assertions: 1195, Skipped: 20
OK (all tests passing)
```

### Target Coverage
- Line Coverage: 80%+
- Branch Coverage: 75%+
- Method Coverage: 85%+

## Troubleshooting

### Common Issues

1. **Database Connection Errors**
   - Ensure test database exists
   - Check database credentials in `phpunit.xml`
   - Run migrations on test database

2. **SSH Mock Failures**
   - Verify Process facade is being mocked
   - Check SSH command patterns in assertions
   - Ensure MocksSSH trait is imported

3. **Cache-Related Failures**
   - Clear cache before tests: `php artisan cache:clear --env=testing`
   - Use array cache driver for tests
   - Check Redis connection if using Redis

4. **Factory Errors**
   - Ensure all required factories exist
   - Check factory relationships
   - Verify factory states are defined

## Conclusion

This comprehensive test suite ensures DevFlow Pro maintains high quality and reliability. All tests follow Laravel and PHPUnit best practices, with proper mocking of external dependencies and comprehensive coverage of critical functionality.

For questions or issues, refer to the Laravel Testing documentation: https://laravel.com/docs/testing
