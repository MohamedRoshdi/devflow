# DevFlow Pro - Browser Tests

This directory contains comprehensive Laravel Dusk browser tests for the DevFlow Pro application.

## Recent Fixes (December 2024)

Fixed 20+ skipped browser tests by:
1. Creating a dedicated `BrowserTestSeeder` for test data
2. Updating test classes to create their own data in `setUp()` methods
3. Removing skip conditions that relied on existing database data

**Previously failing tests now fixed:**
- ✅ `DeploymentShowTest.php` - All 30 tests now runnable
- ✅ `DomainManagerTest.php` - All 30 tests now runnable

## Test Coverage

The `DashboardTest.php` file includes 22 comprehensive test cases covering:

### Core Functionality Tests
1. ✅ Dashboard page loads successfully for authenticated user
2. ✅ Stats cards are visible with correct data
3. ✅ Quick Actions panel is visible with all buttons
4. ✅ Deploy All button shows confirmation dialog and works
5. ✅ Clear Caches button works and shows notification
6. ✅ Activity feed section loads with recent activities
7. ✅ Server health section shows server status
8. ✅ Deployment timeline chart is visible

### UI/UX Tests
9. ✅ Dashboard responds to dark/light mode toggle
10. ✅ Dashboard widgets can be collapsed/expanded
11. ✅ Dashboard auto-refreshes (poll functionality)
12. ✅ Navigation links work correctly
13. ✅ User dropdown menu works
14. ✅ Mobile responsiveness at different viewport sizes (375px, 768px, 1920px)

### Navigation Tests
15. ✅ Quick action links navigate to correct pages
16. ✅ Stats cards show correct online/offline counts
17. ✅ Hero section displays correct stats

### Advanced Features Tests
18. ✅ Customize Layout button toggles edit mode
19. ✅ Activity feed shows Load More button
20. ✅ Dashboard handles no data gracefully
21. ✅ SSL expiring warning is displayed
22. ✅ Deployment timeline shows correct status colors

## Prerequisites

Before running the tests, ensure you have:

1. **Chrome/Chromium Browser** installed
2. **ChromeDriver** installed (matching your Chrome version)
3. **Laravel Dusk** package installed
4. **Testing database** configured and seeded

## Installation

### 1. Install Laravel Dusk (if not already installed)

```bash
composer require --dev laravel/dusk
php artisan dusk:install
```

### 2. Install ChromeDriver

```bash
# Automatically detect and install the correct ChromeDriver version
php artisan dusk:chrome-driver --detect

# Or specify a version manually
php artisan dusk:chrome-driver 128
```

### 3. Configure Testing Environment

Create or update your `.env.dusk.local` file:

```env
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

# Or use a separate testing database
DB_CONNECTION=mysql
DB_DATABASE=devflow_test
DB_USERNAME=root
DB_PASSWORD=
```

## Running the Tests

### 1. First-Time Setup: Seed Test Database

**Important:** Before running browser tests for the first time, seed the test database:

```bash
# Run migrations
php artisan migrate --env=testing

# Seed browser test data (creates users, servers, projects, deployments, domains)
php artisan db:seed --class=BrowserTestSeeder --env=testing
```

This creates:
- Test admin user (admin@devflow.test / password)
- 2 test servers with Docker
- 4 test projects (Laravel, Shopware, Multi-tenant, SaaS)
- 20 deployments in various states
- Domains for projects

### 2. Run Browser Tests

```bash
# Run all browser tests
php artisan dusk

# Run all dashboard tests
php artisan dusk tests/Browser/DashboardTest.php

# Run deployment tests
php artisan dusk tests/Browser/DeploymentShowTest.php

# Run domain manager tests
php artisan dusk tests/Browser/DomainManagerTest.php
```

### Run Specific Test

```bash
# Run a specific test method
php artisan dusk --filter test_dashboard_page_loads_successfully_for_authenticated_user
```

### Run Tests in Headless Mode

```bash
# For CI/CD environments
php artisan dusk --without-tty
```

### Run Tests with Visible Browser (for debugging)

Edit `.env.dusk.local` and set:

```env
DUSK_HEADLESS_DISABLED=true
```

Then run:

```bash
php artisan dusk tests/Browser/DashboardTest.php
```

## Test Data Management

### Browser Test Seeder

The `BrowserTestSeeder` creates consistent test data for all browser tests:

**Created by Seeder:**
- 1 Test admin user (admin@devflow.test / password)
- 2 Test servers (online status, with Docker)
- 4 Test projects (Laravel, Shopware, Multi-tenant, SaaS)
- 20 Deployments across all projects (5 per project)
- 6 Domains (for projects that need them)

**Created by Individual Tests:**
- **DashboardTest**: 4 Servers, 7 Projects, 8 Deployments, 4 SSL Certificates, 5 Health Checks
- **DeploymentShowTest**: Uses seeded data + creates test-specific deployments
- **DomainManagerTest**: Uses seeded data + creates test-specific domains

### Data Cleanup

- **BrowserTestSeeder data**: Persists across test runs (use `firstOrCreate` to avoid duplicates)
- **Test-specific data**: Created inline in tests, not automatically cleaned up
- To reset test database: `php artisan migrate:fresh --env=testing && php artisan db:seed --class=BrowserTestSeeder --env=testing`

## Test Debugging

### Enable Screenshots on Failure

Screenshots are automatically captured on test failures and stored in:

```
tests/Browser/screenshots/
```

### Enable Browser Console Logs

Add to your test:

```php
$browser->dump(); // Dump current page
$browser->screenshot('debug'); // Take manual screenshot
```

### Slow Down Tests for Debugging

Add pause statements:

```php
$browser->pause(2000); // Pause for 2 seconds
```

## Common Issues and Solutions

### Issue: ChromeDriver Version Mismatch

**Solution:**
```bash
php artisan dusk:chrome-driver --detect
```

### Issue: Tests Fail with "Cannot find Chrome binary"

**Solution:**
- Install Chrome/Chromium browser
- Set `DUSK_CHROME_BINARY` in `.env.dusk.local`:

```env
DUSK_CHROME_BINARY=/usr/bin/google-chrome
# Or for Chromium
DUSK_CHROME_BINARY=/usr/bin/chromium-browser
```

### Issue: Database Errors

**Solution:**
- Ensure testing database exists and is accessible
- Run migrations: `php artisan migrate --env=testing`
- Seed test data: `php artisan db:seed --class=BrowserTestSeeder --env=testing`
- Check `.env.dusk.local` database configuration

### Issue: Tests Being Skipped (No Data Found)

If you see messages like "No deployment found in database" or "No project available for testing":

**Solution:**
1. Ensure you've seeded the browser test database:
   ```bash
   php artisan db:seed --class=BrowserTestSeeder --env=testing
   ```

2. Verify data exists:
   ```bash
   php artisan tinker --env=testing
   >>> User::where('email', 'admin@devflow.test')->count()  // Should be 1
   >>> Project::count()  // Should be at least 4
   >>> Deployment::count()  // Should be at least 20
   ```

3. If data is missing, reset and reseed:
   ```bash
   php artisan migrate:fresh --env=testing
   php artisan db:seed --class=BrowserTestSeeder --env=testing
   ```

### Issue: Timeout Errors

**Solution:**
- Increase timeout in `tests/DuskTestCase.php`:

```php
protected function driver(): RemoteWebDriver
{
    // ... existing code ...
    $options->addArguments(['--window-size=1920,1080']);
    $options->setExperimentalOption('prefs', [
        'profile.default_content_setting_values.notifications' => 2,
    ]);

    return RemoteWebDriver::create(
        'http://localhost:9515',
        DesiredCapabilities::chrome()->setCapability(
            ChromeOptions::CAPABILITY, $options
        ),
        60000, // Connection timeout (60 seconds)
        60000  // Request timeout (60 seconds)
    );
}
```

### Issue: Livewire Component Not Responding

**Solution:**
- Add `waitForLivewire()` before assertions
- Increase wait times: `->waitFor('.selector', 10)`
- Use `->pause(1000)` after Livewire actions

## CI/CD Integration

### GitHub Actions Example

```yaml
name: Dusk Tests

on: [push, pull_request]

jobs:
  dusk:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v2

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.4
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite

      - name: Install Dependencies
        run: composer install --prefer-dist --no-interaction

      - name: Install ChromeDriver
        run: php artisan dusk:chrome-driver --detect

      - name: Start Chrome Driver
        run: ./vendor/laravel/dusk/bin/chromedriver-linux &

      - name: Run Laravel Server
        run: php artisan serve &

      - name: Run Dusk Tests
        run: php artisan dusk tests/Browser/DashboardTest.php

      - name: Upload Screenshots
        if: failure()
        uses: actions/upload-artifact@v2
        with:
          name: screenshots
          path: tests/Browser/screenshots
```

## Best Practices

1. **Always use `waitForText()` or `waitFor()` before assertions** to ensure elements are loaded
2. **Use Livewire-aware methods** like `waitForLivewire()` when interacting with Livewire components
3. **Keep tests isolated** - each test should be independent and not rely on other tests
4. **Use factories** for test data creation to ensure consistency
5. **Clean up after tests** - use `DatabaseMigrations` to ensure a clean slate
6. **Test mobile responsiveness** - always test at multiple viewport sizes
7. **Handle async operations** - use appropriate wait methods for AJAX and Livewire updates

## Performance Tips

1. **Run tests in parallel** (requires Laravel Dusk 7.0+):
   ```bash
   php artisan dusk --parallel
   ```

2. **Use SQLite in-memory database** for faster tests:
   ```env
   DB_CONNECTION=sqlite
   DB_DATABASE=:memory:
   ```

3. **Disable unnecessary features** in testing:
   ```env
   DEBUGBAR_ENABLED=false
   TELESCOPE_ENABLED=false
   ```

## Contributing

When adding new dashboard features:

1. Add corresponding browser tests to `DashboardTest.php`
2. Follow existing test naming conventions: `test_feature_description`
3. Add proper documentation in test docblocks
4. Ensure tests are independent and can run in any order
5. Test both success and failure scenarios
6. Include mobile responsiveness tests for new features

## Support

For issues or questions:
- Check Laravel Dusk documentation: https://laravel.com/docs/dusk
- Review test failures in `tests/Browser/screenshots/`
- Check browser console logs for JavaScript errors
- Verify ChromeDriver version matches installed Chrome version

## Test Execution Time

Expected execution time for full test suite:
- **Headless mode:** ~2-3 minutes
- **Visible browser mode:** ~3-4 minutes
- **With database seeding:** Add 30-60 seconds

## Maintenance

### Regular Maintenance Tasks

1. **Update ChromeDriver** when Chrome browser updates:
   ```bash
   php artisan dusk:chrome-driver --detect
   ```

2. **Update test data** when dashboard features change
3. **Review and update selectors** when UI changes
4. **Add tests for new features** as they're developed

## Version History

- **v1.0.0** (2024-12-05) - Initial comprehensive test suite with 22 test cases
  - Core functionality tests
  - UI/UX tests
  - Navigation tests
  - Advanced features tests
  - Mobile responsiveness tests
