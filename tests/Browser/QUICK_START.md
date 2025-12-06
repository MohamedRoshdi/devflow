# Quick Start - Authentication Tests

## Run Tests Immediately

### 1. Install Dependencies (if not already installed)
```bash
composer require --dev laravel/dusk
php artisan dusk:install
php artisan dusk:chrome-driver --detect
```

### 2. Run All Authentication Tests
```bash
php artisan dusk tests/Browser/AuthenticationTest.php
```

### 3. Run Tests with Visible Browser (Recommended for First Run)
```bash
DUSK_HEADLESS_DISABLED=true php artisan dusk tests/Browser/AuthenticationTest.php
```

## Quick Commands

```bash
# Run single test
php artisan dusk --filter=login_with_valid_credentials_redirects_to_dashboard

# Run with browser visible (debugging)
DUSK_HEADLESS_DISABLED=true php artisan dusk tests/Browser/AuthenticationTest.php

# Run and show all output
php artisan dusk tests/Browser/AuthenticationTest.php --verbose

# Run specific group of tests (validation tests)
php artisan dusk --filter=validation tests/Browser/AuthenticationTest.php
```

## Test Results Location

- **Screenshots**: `tests/Browser/screenshots/`
- **Console Logs**: `tests/Browser/console/`
- **Test Output**: Terminal

## Expected Output

```
✓ login page loads with all form elements
✓ login with valid credentials redirects to dashboard
✓ login with invalid credentials shows error
✓ login validation empty email
✓ login validation empty password
✓ login validation invalid email format
✓ logout functionality works correctly
✓ authenticated user can access dashboard
✓ unauthenticated user redirected to login
✓ remember me checkbox functionality
✓ login page supports dark mode
✓ forgot password link navigates correctly
✓ login form shows loading state
✓ multiple failed login attempts
✓ login email field has autofocus
✓ login displays session status messages
✓ successful login updates last login timestamp
✓ login page responsive layout
✓ login redirects to intended destination
✓ login form has proper accessibility features

Tests: 20 passed (20 tests, 150+ assertions)
```

## Troubleshooting

### "ChromeDriver not found"
```bash
php artisan dusk:chrome-driver --detect
```

### "Connection refused to localhost:9515"
```bash
# Kill existing ChromeDriver
pkill -f chromedriver
# Restart Dusk
php artisan dusk:chrome-driver
```

### "Database not found"
```bash
mysql -u root -p -e "CREATE DATABASE devflow_pro_test;"
php artisan migrate --database=mysql --env=testing
```

### Tests failing randomly
```bash
# Increase wait times in tests
# Or run individually to isolate issues
php artisan dusk --filter=test_name
```

## Test Credentials

Tests automatically create:
- **Email**: admin@devflow.com
- **Password**: DevFlow@2025

## Time to Complete

- **Full Test Suite**: ~5-8 minutes
- **Individual Tests**: 5-30 seconds each

---

For detailed documentation, see: `README_AUTHENTICATION_TESTS.md`
