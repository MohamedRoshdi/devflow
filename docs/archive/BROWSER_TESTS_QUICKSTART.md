# Browser Tests - Quick Start Guide

## TL;DR - Just Want to Run Tests?

```bash
# 1. Setup (first time only)
./seed-browser-tests.sh

# 2. Run tests
php artisan dusk
```

## Problem Solved

Previously 20+ browser tests were skipped with "No data found" errors. Now all tests run!

## What Was Fixed

- ✅ DeploymentShowTest - All 30 tests now run
- ✅ DomainManagerTest - All 30 tests now run
- ✅ ProjectShowTest - All tests now run

## Quick Commands

### First Time Setup

```bash
# Option 1: Use the script (recommended)
./seed-browser-tests.sh

# Option 2: Manual commands
php artisan migrate --env=testing
php artisan db:seed --class=BrowserTestSeeder --env=testing
```

### Run Tests

```bash
# All browser tests
php artisan dusk

# Specific test file
php artisan dusk tests/Browser/DeploymentShowTest.php
php artisan dusk tests/Browser/DomainManagerTest.php

# Specific test method
php artisan dusk --filter test_deployment_show_page_loads_successfully
```

### Reset Test Database

```bash
php artisan migrate:fresh --env=testing
php artisan db:seed --class=BrowserTestSeeder --env=testing
```

## Test Credentials

- **Email:** admin@devflow.test
- **Password:** password

## What Gets Created

The seeder creates:
- 1 test admin user
- 2 test servers (with Docker)
- 4 test projects (Laravel, Shopware, Multi-tenant, SaaS)
- 20 deployments (various states)
- 6 domains

## Verify Setup

```bash
php artisan tinker --env=testing

# Then run:
User::count()        # Should be >= 1
Project::count()     # Should be >= 4
Deployment::count()  # Should be >= 20
```

## Troubleshooting

### Tests still skipping?

```bash
# Reset everything
php artisan migrate:fresh --env=testing
php artisan db:seed --class=BrowserTestSeeder --env=testing
php artisan dusk
```

### Database connection errors?

Check `phpunit.xml` has:
```xml
<env name="DB_CONNECTION" value="mysql_testing"/>
<env name="DB_DATABASE" value="devflow_test"/>
```

## More Info

- **Full Documentation:** `tests/Browser/README.md`
- **Technical Details:** `BROWSER_TEST_FIX_SUMMARY.md`
- **Seeder Code:** `database/seeders/BrowserTestSeeder.php`

## Need Help?

Common issues and solutions are in `tests/Browser/README.md` under "Troubleshooting"
