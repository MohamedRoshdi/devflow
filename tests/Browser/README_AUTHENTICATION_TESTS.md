# Authentication Tests - Laravel Dusk

## Overview
Comprehensive browser tests for DevFlow Pro authentication functionality using Laravel Dusk.

## Test File
`tests/Browser/AuthenticationTest.php`

## Prerequisites

### 1. Install Laravel Dusk
```bash
composer require --dev laravel/dusk
php artisan dusk:install
```

### 2. Install ChromeDriver
```bash
php artisan dusk:chrome-driver
```

### 3. Configure Environment
Make sure your `.env.dusk.local` file has the correct database configuration:
```env
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_DATABASE=devflow_pro_test
```

### 4. Create Test Database
```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS devflow_pro_test;"
```

## Running Tests

### Run All Authentication Tests
```bash
php artisan dusk tests/Browser/AuthenticationTest.php
```

### Run Specific Test Method
```bash
php artisan dusk --filter=login_with_valid_credentials_redirects_to_dashboard
```

### Run Tests with Visible Browser (No Headless)
```bash
DUSK_HEADLESS_DISABLED=true php artisan dusk tests/Browser/AuthenticationTest.php
```

### Run Tests and Keep Browser Open on Failure
```bash
php artisan dusk --without-tty tests/Browser/AuthenticationTest.php
```

## Test Coverage

### ✅ Test 1: Login Page Structure
- Verifies all form elements are present
- Checks email, password, remember me, submit button
- Validates links (forgot password, admin contact)

### ✅ Test 2: Successful Login
- Tests login with valid credentials
- Verifies redirect to dashboard
- Confirms authentication status

### ✅ Test 3: Failed Login
- Tests invalid credentials
- Verifies error message display
- Confirms no authentication occurs

### ✅ Test 4-6: Form Validation
- Empty email field validation
- Empty password field validation
- Invalid email format validation

### ✅ Test 7: Logout Functionality
- Tests logout process
- Verifies session clearing
- Confirms redirect to login

### ✅ Test 8: Authenticated Access
- Verifies authenticated users can access dashboard

### ✅ Test 9: Unauthenticated Redirect
- Tests that guests are redirected to login
- Verifies protection of routes

### ✅ Test 10: Remember Me
- Tests remember me checkbox functionality
- Verifies checkbox state

### ✅ Test 11: Dark Mode Support
- Tests dark mode classes are present
- Verifies styling compatibility

### ✅ Test 12: Forgot Password Link
- Tests navigation to password reset
- Verifies link functionality

### ✅ Test 13: Livewire Loading States
- Tests wire:loading functionality
- Verifies loading text appears

### ✅ Test 14: Multiple Failed Attempts
- Tests repeated failed login attempts
- Verifies consistent error handling

### ✅ Test 15: Autofocus
- Tests email field autofocus
- Verifies user experience optimization

### ✅ Test 16: Session Messages
- Tests status message display
- Verifies registration closed message

### ✅ Test 17: Login Timestamp
- Tests last_login_at update
- Verifies database changes

### ✅ Test 18: Responsive Layout
- Tests mobile, tablet, desktop views
- Verifies responsive design

### ✅ Test 19: Intended Redirect
- Tests redirect to intended destination
- Verifies intended() middleware

### ✅ Test 20: Accessibility
- Tests proper labels
- Verifies ARIA attributes and semantic HTML

## Test Credentials

The tests use the following credentials (created automatically):
- **Email**: admin@devflow.com
- **Password**: DevFlow@2025

## Screenshots

All tests capture screenshots at important steps. Screenshots are saved to:
```
tests/Browser/screenshots/
```

Screenshot naming convention:
- `01-login-page-loaded.png`
- `02-valid-credentials-entered.png`
- `03-invalid-credentials-entered.png`
- etc.

## Troubleshooting

### ChromeDriver Issues
```bash
# Update ChromeDriver to match your Chrome version
php artisan dusk:chrome-driver --detect
```

### Database Issues
```bash
# Reset test database
php artisan migrate:fresh --database=mysql --env=dusk.local
```

### Port Already in Use
```bash
# Kill existing ChromeDriver processes
pkill -f chromedriver
```

### Slow Tests
```bash
# Run tests in parallel (requires paratest)
php artisan dusk --parallel
```

## Best Practices

1. **Always run tests in headless mode for CI/CD**
   ```bash
   php artisan dusk
   ```

2. **Use screenshots for debugging**
   - Screenshots are automatically taken at key points
   - Check screenshots when tests fail

3. **Database Migrations**
   - Tests use DatabaseMigrations trait
   - Database is reset before each test

4. **Livewire Wait Times**
   - Tests include proper wait times for Livewire components
   - Adjust `pause()` times if tests are flaky

5. **Browser Console Errors**
   ```bash
   # Check browser console for JavaScript errors
   php artisan dusk --log-console
   ```

## CI/CD Integration

### GitHub Actions Example
```yaml
- name: Run Dusk Tests
  run: |
    php artisan dusk:chrome-driver --detect
    php artisan dusk --without-tty
```

### GitLab CI Example
```yaml
test:dusk:
  script:
    - php artisan dusk:chrome-driver --detect
    - php artisan dusk --without-tty
```

## Performance

Expected test execution time: **~5-8 minutes**

Individual test times:
- Fast tests (validation): 2-5 seconds
- Medium tests (login/logout): 5-10 seconds
- Slow tests (multiple attempts, responsive): 10-20 seconds

## Notes

- Tests are designed to be independent and can run in any order
- Each test creates its own user via DatabaseMigrations
- Screenshots help debug failed tests
- All tests follow PHPStan Level 8 compliance
- Tests are documented with clear docblocks

## Maintenance

### Adding New Tests
1. Follow the existing test structure
2. Use descriptive test method names
3. Include proper docblocks
4. Take screenshots at important steps
5. Use proper assertions

### Updating Tests
- Keep tests updated with UI changes
- Update selectors if HTML structure changes
- Adjust wait times if Livewire components change
- Update credentials if authentication changes

## Support

For issues or questions:
1. Check Laravel Dusk documentation: https://laravel.com/docs/dusk
2. Review test screenshots for debugging
3. Check browser console logs
4. Verify ChromeDriver version matches Chrome

---

**Last Updated**: 2025-12-05
**Laravel Version**: 12.x
**Dusk Version**: Latest
**PHP Version**: 8.4
