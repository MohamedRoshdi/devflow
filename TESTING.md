# DevFlow Pro - Testing Documentation

> Comprehensive test suite with 11,000+ tests covering browser, unit, feature, integration, and security testing.

## ⚠️ Current Status (December 2025 - v6.9.2)

| Suite | Status | Notes |
|-------|--------|-------|
| Unit/Models | ✅ Passing | 645 tests |
| Unit/Services | ✅ Passing | 900+ tests |
| Unit/Policies | ✅ Passing | 37 tests |
| Unit/Console | ✅ Passing | CommandsTest 69/69 |
| Unit/Controllers | ✅ Passing | ControllersTest 68/68 |
| Unit/Middleware | ✅ Passing | MiddlewareTest all passing |
| Feature/Livewire | ✅ Passing | 71 test files, 313+ tests |
| Feature/API | ✅ Passing | 8 test files, 200+ tests |
| Feature/Integration | ✅ Passing | 8 test files |
| Security | ✅ Passing | 6 test files |
| Browser | ✅ Passing | 90+ tests verified (AdminTest, AuthenticationTest) |

**Fixes Applied (v6.9.2):**
- **PostgreSQL Search Compatibility**: Fixed case-insensitive search in `HelpContent` and `LogEntry` models using `LOWER()` function
- **HelpContentRelated Cast**: Changed `relevance_score` cast from `float` to `integer` to match database schema
- **Test Bootstrap**: Added `opcache_reset()` for development environments with disabled timestamp validation

**Fixes Applied (v6.8.0):**
- **RefreshDatabase Trait**: Commented out `use RefreshDatabase;` in all test files to prevent MySQL metadata locks
- **DatabaseTransactions**: All tests now use `DatabaseTransactions` from base TestCase for proper test isolation
- **API Tests**: Fixed 8 API test files (200+ tests now passing)
- **Integration Tests**: Fixed 8 integration test files
- **Security Tests**: Fixed 6 security test files
- **Livewire Tests**: Fixed 71 test files with mock constraints and assertion updates

**Fixes Applied (v6.3.0):**
- `DeploymentScript`: Fixed variable substitution to use direct key replacement
- `HelpContent*` models: Added HasFactory trait to related models
- `PipelineStage`: Added 'migration' check for database icon mapping
- `PipelineStageRun`: Fixed duration calculation order
- `SecurityScan`: Fixed duration calculation parameter order
- `HelpContentTranslationFactory`: Added unique() and expanded locale options
- `HelpInteractionFactory`: Fixed 'viewed' to 'view' enum value
- `UserFactory`: Added uniqid() to prevent email collisions
- Multiple test files: Use make() instead of create() for invalid enum values
- `BackupModelsTest`: Extended TestCase with proper setUp for table cleanup
- `CoreModelsTest`: Fixed status_color/status_icon expectations per HealthScoreMapper
- `PoliciesTest`: Fixed ownership-based authorization tests

**Fixes Applied (v6.2.0):**
- SQLite compatibility in migrations (driver detection for index checking)
- TestCase changed to `DatabaseTransactions` (from custom MySQL trait)
- `AlertNotificationService::formatValue` accepts `string|int|float`
- `PipelineRunFactory` now includes `pipeline_id`
- `ProjectFactory` slug uniqueness with numerify suffix
- Test isolation cleanup (BackupSchedule, Server, DatabaseBackup)
- Removed non-existent command tests (`CheckSSLCommand`, `SSLRenewCommand`)
- ControllersTest updated for restrictive policies (403 for non-owners)
- Webhook tests use `webhook_secret` token correctly
- `ClusterManager` uses correct model fields (`api_server_url`, `is_active`)
- `DeployProjectJob` timeout updated (1200 → 1800)

---

## Quick Start

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
php artisan test --testsuite=Browser

# Run with coverage
php artisan test --coverage

# Run specific test file
php artisan test tests/Unit/Services/KubernetesServiceTest.php
```

---

## Test Coverage Summary

| Category | Files | Tests | Coverage |
|----------|-------|-------|----------|
| **Browser Tests** | 97 | ~2,500 | 90%+ |
| **Unit - Services** | 42 | ~900 | 95%+ |
| **Unit - Models** | 11 | ~492 | 95%+ |
| **Unit - Commands** | 1 | ~100 | 100% |
| **Unit - Requests** | 1 | 172 | 100% |
| **Feature/API** | 7 | ~245 | 85%+ |
| **Integration** | 1 | 56 | 80%+ |
| **Security** | 2 | ~80 | 90%+ |
| **Performance** | 1 | ~30 | Basic |
| **TOTAL** | **163+** | **4,550+** | **~90%** |

---

## Test Structure

```
tests/
├── Browser/                    # Laravel Dusk browser tests
│   ├── AuthenticationTest.php
│   ├── DashboardTest.php
│   ├── ProjectManagementTest.php
│   ├── ServerManagementTest.php
│   ├── DeploymentTest.php
│   ├── SettingsTest.php
│   └── ... (97 test files)
│
├── Feature/                    # Feature/integration tests
│   ├── Api/
│   │   └── ApiEndpointTest.php
│   ├── Integration/
│   │   └── WorkflowIntegrationTest.php
│   ├── DeploymentTest.php
│   ├── ProjectManagementTest.php
│   └── ServerManagementTest.php
│
├── Unit/                       # Unit tests
│   ├── Console/
│   │   └── CommandsTest.php
│   ├── Controllers/
│   │   └── ControllersTest.php
│   ├── Jobs/
│   │   └── JobsTest.php
│   ├── Livewire/
│   │   ├── DashboardAdminComponentsTest.php
│   │   ├── ProjectDeploymentComponentsTest.php
│   │   ├── ServerComponentsTest.php
│   │   └── SettingsUtilityComponentsTest.php
│   ├── Models/
│   │   ├── CoreModelsTest.php
│   │   ├── InfrastructureModelsTest.php
│   │   ├── BackupModelsTest.php
│   │   ├── TeamAuthModelsTest.php
│   │   └── AdditionalModelsTest.php
│   ├── Policies/
│   │   └── PoliciesTest.php
│   ├── Requests/
│   │   └── FormRequestValidationTest.php
│   └── Services/
│       ├── AuditServiceTest.php
│       ├── DeploymentApprovalServiceTest.php
│       ├── DockerServiceTest.php
│       ├── GitHubServiceTest.php
│       ├── KubernetesServiceTest.php
│       ├── SecurityScoreServiceTest.php
│       └── ... (42 service test files)
│
├── Security/                   # Security/penetration tests
│   ├── SecurityAudit.php
│   └── PenetrationTest.php
│
└── Performance/                # Performance tests
    └── PerformanceTestSuite.php
```

---

## Browser Tests (Laravel Dusk)

Browser tests use Laravel Dusk for end-to-end UI testing of Livewire components.

### Running Browser Tests

```bash
# Start Chrome driver
php artisan dusk:chrome-driver

# Run all browser tests
php artisan dusk

# Run specific browser test
php artisan dusk tests/Browser/DashboardTest.php

# Run with specific filter
php artisan dusk --filter=test_dashboard_loads
```

### Key Browser Test Files

| Test File | Tests | Coverage |
|-----------|-------|----------|
| `AuthenticationTest.php` | 25+ | Login, Register, Logout |
| `DashboardTest.php` | 30+ | Dashboard widgets, stats |
| `ProjectManagementTest.php` | 40+ | Project CRUD |
| `ServerManagementTest.php` | 35+ | Server CRUD |
| `DeploymentTest.php` | 30+ | Deployment workflows |
| `SettingsTest.php` | 27 | All settings pages |
| `SystemSettingsTest.php` | 40 | System configuration |
| `SecurityTest.php` | 25+ | Security features |

### Test Pattern

```php
<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class ExampleTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::firstOrCreate(
            ['email' => 'admin@devflow.test'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
    }

    public function test_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/dashboard')
                ->assertSee('Welcome');
        });
    }
}
```

---

## Unit Tests

### Service Tests

All 42 services have comprehensive unit tests:

```bash
# Run all service tests
php artisan test tests/Unit/Services/

# Run specific service test
php artisan test tests/Unit/Services/KubernetesServiceTest.php
```

| Service | Tests | Coverage |
|---------|-------|----------|
| `KubernetesService` | 38 | Cluster, pods, deployments |
| `DockerService` | 30+ | Container management |
| `GitHubService` | 25+ | OAuth, repos, webhooks |
| `DeploymentService` | 35+ | Deployment workflows |
| `SecurityScoreService` | 37 | Security scoring |
| `Fail2banService` | 53 | Intrusion prevention |
| `SSHSecurityService` | 41 | SSH hardening |

### Model Tests

All 50+ models have tests for:
- Fillable attributes
- Cast configurations
- Relationships
- Scopes
- Accessors/Mutators

```bash
php artisan test tests/Unit/Models/
```

### Request Validation Tests

All API Form Requests are tested:

```php
// tests/Unit/Requests/FormRequestValidationTest.php
// 172 tests covering:
- StoreProjectRequest (65 tests)
- StoreServerRequest (42 tests)
- UpdateProjectRequest (34 tests)
- UpdateServerRequest (31 tests)
```

---

## Feature Tests

### API Endpoint Tests

Complete REST API coverage:

```bash
php artisan test tests/Feature/Api/
```

| Endpoint | Methods Tested |
|----------|---------------|
| `/api/v1/projects` | GET, POST, PUT, DELETE |
| `/api/v1/servers` | GET, POST, PUT, DELETE |
| `/api/v1/deployments` | GET, POST, Rollback |
| `/api/v1/webhooks` | GitHub, GitLab, Bitbucket |

### Integration Tests

End-to-end workflow testing:

```bash
php artisan test tests/Feature/Integration/
```

**Workflows Tested:**
1. Pipeline Execution (10 tests)
2. Multi-Tenant Deployment (12 tests)
3. Webhook Delivery (10 tests)
4. Bulk Server Operations (8 tests)
5. Security Scanning (10 tests)

---

## Security Tests

### Penetration Testing

```bash
php artisan test tests/Security/PenetrationTest.php
```

**Security Areas Tested (39 tests):**

| Category | Tests | Payloads |
|----------|-------|----------|
| XSS Prevention | 5 | 15 vectors |
| SQL Injection | 5 | 15 vectors |
| Race Conditions | 4 | Concurrent requests |
| Mass Assignment | 5 | Hidden fields |
| API Token Abuse | 5 | Enumeration, escalation |
| Authentication | 5 | Session, brute force |
| Authorization | 4 | Access control |
| CSRF Protection | 1 | Token validation |
| Input Validation | 5 | Path traversal, XXE |

### Security Audit

```bash
php artisan test tests/Security/SecurityAudit.php
```

---

## Performance Tests

```bash
php artisan test tests/Performance/
```

**Areas Tested:**
- Database query performance
- N+1 query detection
- Cache performance
- API response times
- Memory usage
- Concurrent user simulation

---

## Test Configuration

### PHPUnit Configuration

```xml
<!-- phpunit.xml -->
<testsuites>
    <testsuite name="Unit">
        <directory>tests/Unit</directory>
    </testsuite>
    <testsuite name="Feature">
        <directory>tests/Feature</directory>
    </testsuite>
    <testsuite name="Browser">
        <directory>tests/Browser</directory>
    </testsuite>
</testsuites>

<php>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
</php>
```

### Test Database

Tests use SQLite in-memory database for speed:

```bash
# Run with SQLite (default)
php artisan test

# Run with MySQL
DB_CONNECTION=mysql php artisan test
```

---

## Writing New Tests

### Browser Test Template

```php
<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Tests\Browser\Traits\LoginViaUI;

class NewFeatureTest extends DuskTestCase
{
    use LoginViaUI;

    public function test_feature_works(): void
    {
        $this->browse(function ($browser) {
            $this->loginViaUI($browser)
                ->visit('/feature')
                ->assertSee('Expected Content');
        });
    }
}
```

### Unit Test Template

```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NewServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NewService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NewService();
    }

    public function test_method_works(): void
    {
        $result = $this->service->method();
        $this->assertTrue($result);
    }
}
```

---

## CI/CD Integration

### GitHub Actions

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
      - name: Install Dependencies
        run: composer install
      - name: Run Tests
        run: php artisan test
```

---

## Troubleshooting

### Common Issues

**1. Database connection errors**
```bash
# Use PostgreSQL for tests (recommended)
DB_CONNECTION=pgsql DB_DATABASE=devflow_test php artisan test

# Or use SQLite for quick local tests (may have cascade issues)
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test
```

**2. Browser tests failing**
```bash
# Update Chrome driver
php artisan dusk:chrome-driver --detect

# Run with debug
php artisan dusk --debug
```

**3. Memory issues**
```bash
# Increase PHP memory
php -d memory_limit=512M artisan test
```

---

## Docker-Based Testing

For environments without PHP installed locally, use Docker:

```bash
# Run all tests via Docker
docker run --rm \
  -v $(pwd):/app \
  -w /app \
  -e APP_ENV=testing \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=:memory: \
  -e CACHE_DRIVER=array \
  -e SESSION_DRIVER=array \
  -e QUEUE_CONNECTION=sync \
  php:8.4-cli \
  php -d memory_limit=1G vendor/bin/phpunit

# Run specific test suite
docker run --rm \
  -v $(pwd):/app \
  -w /app \
  -e APP_ENV=testing \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=:memory: \
  php:8.4-cli \
  php vendor/bin/phpunit --testsuite=Feature

# Using docker-compose (included in repo)
docker-compose run --rm test
```

### Available Docker Files
- `Dockerfile.test` - Test-specific Docker image
- `docker-compose.yml` - Docker Compose configuration for testing
- `phpunit.dusk.xml` - PHPUnit configuration for browser tests

---

## Contributing

When adding new features:
1. Write tests first (TDD preferred)
2. Ensure all tests pass: `php artisan test`
3. Check code style: `./vendor/bin/pint`
4. Run static analysis: `./vendor/bin/phpstan`

---

## Resources

- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [Laravel Dusk Documentation](https://laravel.com/docs/dusk)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Mockery Documentation](https://docs.mockery.io/)
