# DevFlow Pro - Quick Test Reference

## Prerequisites

1. **Create Test Database**
```bash
mysql -u root -p
```

```sql
CREATE DATABASE devflow_pro_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON devflow_pro_test.* TO 'your_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

2. **Configure Environment**
Ensure `phpunit.xml` has correct database settings:
```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_DATABASE" value="devflow_pro_test"/>
<env name="DB_USERNAME" value="your_user"/>
<env name="DB_PASSWORD" value="your_password"/>
```

3. **Run Migrations**
```bash
php artisan migrate --database=mysql --env=testing
```

## Running Tests

### All Tests
```bash
# Run all tests
php artisan test

# With verbose output
php artisan test --verbose

# With parallel execution (faster)
php artisan test --parallel
```

### Specific Test Suites
```bash
# Unit tests only
php artisan test --testsuite=Unit

# Feature tests only
php artisan test --testsuite=Feature
```

### Specific Test Files
```bash
# Service tests
php artisan test tests/Unit/Services/ServerMetricsServiceTest.php
php artisan test tests/Unit/Services/DatabaseBackupServiceTest.php
php artisan test tests/Unit/Services/PipelineExecutionServiceTest.php

# Livewire component tests
php artisan test tests/Feature/Livewire/DashboardTest.php
```

### Specific Test Methods
```bash
# Run single test by filter
php artisan test --filter it_collects_metrics_from_online_server

# Run tests matching pattern
php artisan test --filter metrics
```

### With Coverage
```bash
# Generate coverage report
php artisan test --coverage

# Generate HTML coverage report (requires xdebug)
php artisan test --coverage-html coverage-report
```

## Test Output Examples

### Successful Test Run
```
   PASS  Tests\Unit\Services\ServerMetricsServiceTest
  ✓ it collects metrics from online server
  ✓ it returns null on metrics collection failure
  ✓ it retrieves metrics history

  Tests:  3 passed
  Time:   0.45s
```

### Failed Test Run
```
   FAIL  Tests\Unit\Services\ServerMetricsServiceTest
  ✓ it collects metrics from online server
  ✗ it returns null on metrics collection failure

  Failed asserting that null is an instance of class "App\Models\ServerMetric".

  at tests/Unit/Services/ServerMetricsServiceTest.php:25
```

## Debugging Tests

### Show Full Error Output
```bash
php artisan test --verbose
```

### Stop on First Failure
```bash
php artisan test --stop-on-failure
```

### Run Single Test with Debugging
```bash
# Add dd() or dump() in test
php artisan test --filter specific_test_name
```

### Check Database State
```php
// In your test
$this->assertDatabaseHas('servers', ['name' => 'Test Server']);
$this->assertDatabaseMissing('servers', ['name' => 'Deleted Server']);
$this->assertDatabaseCount('servers', 3);
```

## Common Issues & Solutions

### Issue: "Base table or view not found"
**Solution:** Run migrations on test database
```bash
php artisan migrate --database=mysql --env=testing
```

### Issue: "Class 'Tests\Traits\CreatesServers' not found"
**Solution:** Ensure trait files exist and use correct namespace
```php
use Tests\Traits\CreatesServers;
```

### Issue: SSH mock not working
**Solution:** Ensure Process facade is imported and mocked
```php
use Illuminate\Support\Facades\Process;

Process::fake([
    '*ssh*' => Process::result(output: 'Success'),
]);
```

### Issue: Tests are slow
**Solution:** Use parallel testing and SQLite
```bash
php artisan test --parallel

# Or modify phpunit.xml to use SQLite
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

## Test Development Workflow

### 1. Write Test First (TDD)
```php
/** @test */
public function it_creates_new_deployment(): void
{
    // Arrange
    $project = Project::factory()->create();

    // Act
    $deployment = $this->deploymentService->create($project);

    // Assert
    $this->assertInstanceOf(Deployment::class, $deployment);
    $this->assertEquals('pending', $deployment->status);
}
```

### 2. Run Test (Should Fail)
```bash
php artisan test --filter it_creates_new_deployment
```

### 3. Implement Feature
```php
public function create(Project $project): Deployment
{
    return Deployment::create([
        'project_id' => $project->id,
        'status' => 'pending',
    ]);
}
```

### 4. Run Test Again (Should Pass)
```bash
php artisan test --filter it_creates_new_deployment
```

## Writing New Tests

### Unit Test Template
```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Tests\Traits\{CreatesProjects, MocksSSH};
use Illuminate\Foundation\Testing\RefreshDatabase;

class YourServiceTest extends TestCase
{
    use RefreshDatabase, CreatesProjects, MocksSSH;

    protected YourService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new YourService();
    }

    /** @test */
    public function it_does_something(): void
    {
        // Arrange
        $data = ['key' => 'value'];

        // Act
        $result = $this->service->doSomething($data);

        // Assert
        $this->assertTrue($result);
    }
}
```

### Livewire Test Template
```php
<?php

namespace Tests\Feature\Livewire;

use Tests\TestCase;
use App\Livewire\YourComponent;
use App\Models\User;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class YourComponentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_renders_successfully(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(YourComponent::class)
            ->assertStatus(200);
    }

    /** @test */
    public function it_performs_action(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(YourComponent::class)
            ->call('yourMethod', 'parameter')
            ->assertDispatched('event-name');
    }
}
```

## Continuous Integration

### Local Pre-commit Hook
Create `.git/hooks/pre-commit`:
```bash
#!/bin/bash

echo "Running tests..."
php artisan test

if [ $? -ne 0 ]; then
    echo "Tests failed! Commit aborted."
    exit 1
fi

echo "All tests passed!"
exit 0
```

Make it executable:
```bash
chmod +x .git/hooks/pre-commit
```

### GitHub Actions
See `TESTING_GUIDE.md` for GitHub Actions configuration example.

## Performance Tips

1. **Use SQLite for faster tests**
   - Tests run 2-3x faster with in-memory database
   - Good for CI/CD pipelines

2. **Run tests in parallel**
   - Laravel 8+ supports parallel testing
   - Automatically uses multiple processes

3. **Mock external services**
   - Don't make real API calls
   - Don't connect to real servers
   - Use Process::fake() for SSH commands

4. **Use factories efficiently**
   - Create minimum required data
   - Use states for common scenarios
   - Avoid creating unnecessary relationships

## Test Assertions Cheatsheet

### Database Assertions
```php
$this->assertDatabaseHas('table', ['column' => 'value']);
$this->assertDatabaseMissing('table', ['column' => 'value']);
$this->assertDatabaseCount('table', 10);
$this->assertSoftDeleted('table', ['id' => 1]);
```

### Livewire Assertions
```php
->assertSet('property', 'value')
->assertNotSet('property', 'value')
->assertCount('items', 5)
->assertSee('text')
->assertDontSee('text')
->assertDispatched('event-name')
->assertNotDispatched('event-name')
```

### HTTP Assertions
```php
->assertStatus(200)
->assertOk()
->assertRedirect()
->assertJson(['key' => 'value'])
->assertJsonFragment(['key' => 'value'])
->assertJsonStructure(['data' => ['id', 'name']])
```

## Resources

- Laravel Testing Docs: https://laravel.com/docs/testing
- PHPUnit Docs: https://phpunit.de/documentation.html
- Livewire Testing: https://livewire.laravel.com/docs/testing
- Pest (Alternative): https://pestphp.com/

## Summary

Current test coverage (as of December 2025):
- ✅ 36+ Unit Tests (Services)
- ✅ 282 Feature Tests (Livewire Components, API, Authentication, Rate Limiting, Webhooks)
- ✅ 69 API Tests (Projects, Servers, Deployments, Endpoints)
- ✅ 20 Authentication Tests (Login, Registration, Password Reset)
- ✅ 11 Webhook Tests (GitHub, GitLab, Bitbucket)
- ✅ 10 Rate Limiting Tests
- ✅ Test Traits and Helpers
- ✅ Factory Enhancements
- ✅ Mock SSH Connections

**Total: 318+ comprehensive tests covering core functionality**

### Docker-Based Testing (PostgreSQL)
```bash
docker run --rm --network host \
  -v $(pwd):/app -w /app \
  -e APP_ENV=testing \
  -e DB_CONNECTION=pgsql_testing \
  -e DB_HOST=127.0.0.1 \
  -e DB_PORT=5433 \
  -e DB_DATABASE=devflow_test \
  -e DB_USERNAME=devflow_test \
  -e DB_PASSWORD=devflow_test_password \
  devflow-test:latest php vendor/bin/phpunit --testsuite=Feature --no-coverage
```

### Run All Tests
```bash
php artisan test --parallel
```
