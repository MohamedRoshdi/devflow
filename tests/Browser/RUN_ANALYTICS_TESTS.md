# Quick Guide: Running Analytics Browser Tests

## Prerequisites

1. **Start Chrome Driver**
   ```bash
   php artisan dusk:chrome-driver
   ```

2. **Ensure Test User Exists**
   The tests will auto-create the test user:
   - Email: `admin@devflow.test`
   - Password: `password`

3. **Ensure Analytics Route Exists**
   Verify `/analytics` route is registered in `routes/web.php`

## Running Tests

### Run All 55 Analytics Tests
```bash
php artisan dusk tests/Browser/AnalyticsTest.php
```

### Run with Verbose Output
```bash
php artisan dusk tests/Browser/AnalyticsTest.php --verbose
```

### Run Specific Test
```bash
# Example: Run only the dashboard load test
php artisan dusk --filter test_analytics_dashboard_page_loads

# Example: Run only deployment statistics tests
php artisan dusk --filter deployment
```

### Run with Browser Display (Not Headless)
```bash
# Edit phpunit.xml or .env.dusk.local
DUSK_HEADLESS_DISABLED=true php artisan dusk tests/Browser/AnalyticsTest.php
```

## Test Output Locations

### Screenshots
All test screenshots saved to:
```
tests/Browser/screenshots/
```

Screenshot naming pattern:
- `analytics-dashboard-page.png`
- `deployment-statistics-cards.png`
- `cpu-usage-chart.png`
- etc. (55 screenshots total)

### Test Reports
JSON reports saved to:
```
storage/app/test-reports/analytics-{timestamp}.json
```

Report includes:
- Test results for all 55 tests
- Summary statistics
- Environment details (user count, project count, deployment count)
- Timestamp

## Expected Test Results

### All Tests Should Pass If:
1. ✅ Analytics page (`/analytics`) exists and loads
2. ✅ User can successfully authenticate
3. ✅ Analytics Livewire component renders
4. ✅ Basic analytics data structure is present

### Common Issues

**Issue**: Tests fail with "Page not found"
- **Fix**: Ensure `/analytics` route exists in `routes/web.php`
- **Fix**: Ensure Analytics Livewire component is registered

**Issue**: Login fails
- **Fix**: Check test user credentials in database
- **Fix**: Verify `LoginViaUI` trait is working

**Issue**: Screenshots show blank page
- **Fix**: Increase wait times in tests (currently 2000ms)
- **Fix**: Check if JavaScript is loading properly

**Issue**: ChromeDriver crashes
- **Fix**: Update ChromeDriver: `php artisan dusk:chrome-driver --detect`
- **Fix**: Restart ChromeDriver process

## Test Categories Covered

1. **Dashboard Access** (4 tests)
2. **Deployment Statistics** (9 tests)
3. **Server Metrics** (6 tests)
4. **Project Activity** (4 tests)
5. **User Activity** (1 test)
6. **Resource Usage** (2 tests)
7. **Cost Analysis** (1 test)
8. **Trend Analysis** (2 tests)
9. **Date Filtering** (2 tests)
10. **Export Functionality** (1 test)
11. **Comparison Views** (1 test)
12. **Top Rankings** (2 tests)
13. **Error Tracking** (1 test)
14. **Success Metrics** (2 tests)
15. **Performance Benchmarks** (4 tests)
16. **UI/UX Quality** (8 tests)
17. **Real-time Features** (4 tests)

**Total: 55 Tests**

## Parallel Test Execution

To run tests faster using multiple processes:

```bash
# Install paratest if not installed
composer require --dev brianium/paratest

# Run tests in parallel (4 processes)
./vendor/bin/paratest -p4 tests/Browser/AnalyticsTest.php
```

## CI/CD Integration

### GitHub Actions Example
```yaml
- name: Run Analytics Browser Tests
  run: |
    php artisan dusk:chrome-driver --detect
    php artisan dusk tests/Browser/AnalyticsTest.php
```

### GitLab CI Example
```yaml
analytics_tests:
  script:
    - php artisan dusk:chrome-driver --detect
    - php artisan dusk tests/Browser/AnalyticsTest.php
  artifacts:
    when: always
    paths:
      - tests/Browser/screenshots/
      - storage/app/test-reports/
```

## Debugging Tips

1. **View Test in Real Browser**
   ```bash
   DUSK_HEADLESS_DISABLED=true php artisan dusk --filter test_analytics_dashboard_page_loads
   ```

2. **Add More Debugging**
   Edit test to add:
   ```php
   ->dump() // Dump page HTML
   ->pause(5000) // Pause for 5 seconds
   ```

3. **Check Laravel Logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **Check Dusk Console Output**
   ```bash
   cat tests/Browser/console/*.log
   ```

## Performance Notes

- Each test takes approximately 3-5 seconds
- Total runtime for all 55 tests: ~3-5 minutes
- Screenshots add minimal overhead
- Test reports are lightweight (< 50KB)

## Maintenance

### Updating Tests
When analytics features change:
1. Update relevant test methods
2. Update assertions to match new UI
3. Update screenshot expectations
4. Re-run full test suite

### Adding New Tests
Follow the pattern:
```php
public function test_new_feature(): void
{
    $this->browse(function (Browser $browser) {
        $this->loginViaUI($browser)
            ->visit('/analytics')
            ->pause(2000)
            ->waitFor('body', 15)
            ->screenshot('new-feature');

        $pageSource = strtolower($browser->driver->getPageSource());
        $hasFeature = str_contains($pageSource, 'expected text');

        $this->assertTrue($hasFeature, 'Feature should be present');
        $this->testResults['new_feature'] = 'New feature works';
    });
}
```

---

**Last Updated**: 2025-12-06
**Test Count**: 55 tests
**Estimated Runtime**: 3-5 minutes
