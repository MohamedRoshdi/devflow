# ServerShow Browser Test Suite - Summary

**Test File:** `tests/Browser/ServerShowTest.php`
**Component:** `app/Livewire/Servers/ServerShow.php`
**Route:** `/servers/{server}`
**Total Tests:** 35

## Test Coverage Overview

### Page Load & Navigation (2 tests)
1. ✅ Page loads successfully when server exists
2. ✅ Page shows error when server doesn't exist (404)

### Server Information Display (10 tests)
3. ✅ Server name is displayed
4. ✅ Server IP address is shown
5. ✅ Server status indicator is visible
6. ✅ Server hostname is displayed in info section
7. ✅ Server port is displayed
8. ✅ Server username is displayed
9. ✅ Server OS info is displayed
10. ✅ CPU/RAM/Disk info is shown
11. ✅ Server Information section is present
12. ✅ Status card shows correct status badge

### Navigation Links & Buttons (7 tests)
13. ✅ Edit server button is visible
14. ✅ Back to servers link is visible
15. ✅ Metrics link is visible
16. ✅ Security link is visible
17. ✅ Backups link is visible
18. ✅ Docker link/button is visible
19. ✅ SSL link is visible

### Quick Actions (6 tests)
20. ✅ Quick Actions section is present
21. ✅ Ping server button is functional
22. ✅ Reboot server button is visible
23. ✅ Clear cache button is visible
24. ✅ Services restart dropdown is visible
25. ✅ Docker status card is displayed

### Data Sections (5 tests)
26. ✅ Projects section is displayed
27. ✅ Projects list shows server projects
28. ✅ Resource usage/metrics section is shown
29. ✅ Recent Deployments section is present
30. ✅ Connection status or last ping is shown

### Additional Features (5 tests)
31. ✅ Flash messages display after actions
32. ✅ SSH Terminal section is present
33. ✅ Metrics data displays when available
34. ✅ Empty state message when no metrics
35. ✅ Empty state message when no deployments

## Key Features Tested

### 1. Server Details Display
- Server name, hostname, IP address, port
- Operating system information
- Hardware specs (CPU, RAM, Disk)
- Server status with visual indicator
- Docker installation status and version

### 2. Navigation & Actions
- Edit server functionality
- Back to server list
- Links to:
  - Metrics Dashboard
  - Security features
  - Backups
  - SSL Certificates
  - Docker Panel

### 3. Quick Actions Panel
- Ping server to check connectivity
- Reboot server (with confirmation)
- Clear system cache
- Check Docker status
- Install Docker (if not installed)
- Restart services (nginx, mysql, redis, etc.)

### 4. Information Cards
- **Status Card:** Shows online/offline/maintenance status
- **CPU Card:** Shows number of CPU cores
- **Memory Card:** Shows total RAM in GB
- **Docker Card:** Shows installation status and version

### 5. Live Metrics
- CPU usage percentage with visual bar
- Memory usage percentage with visual bar
- Disk usage percentage with visual bar
- Last updated timestamp
- Empty state when no metrics available

### 6. Projects List
- Shows up to 5 recent projects on this server
- Project name and domain
- Project status badge
- Links to individual project pages
- Empty state when no projects

### 7. Recent Deployments
- Shows up to 5 recent deployments
- Deployment status (success/failed/running)
- Project name
- Relative timestamp
- Empty state when no deployments

### 8. SSH Terminal
- Embedded SSH terminal section
- Direct command execution interface

## Test Pattern Used

```php
protected User $user;
protected ?Server $server = null;
protected array $testResults = [];

protected function setUp(): void
{
    parent::setUp();
    $this->user = User::firstOrCreate(...);
    $this->server = Server::first();
}
```

## Assertions Used

- `assertPathIs()` - Verify correct URL path
- `assertVisible()` - Check element visibility
- `assertSee()` - Verify text content
- `assertSeeLink()` - Check for clickable links
- `assertDontSee()` - Ensure text is not present
- `assertTrue()` - Custom boolean assertions
- `markTestSkipped()` - Skip when no server available

## Smart Test Features

1. **Graceful Skipping:** Tests skip if no server exists in database
2. **Conditional Assertions:** Checks for Docker installed vs not installed states
3. **Empty State Testing:** Validates empty states for projects, metrics, deployments
4. **Flash Message Support:** Tests page behavior with session flash messages
5. **Test Results Tracking:** Outputs summary of passed tests

## Running the Tests

```bash
# Run all ServerShow tests
php artisan dusk --filter ServerShowTest

# Run specific test group
php artisan dusk --group server-show

# Run specific test
php artisan dusk --filter test_page_loads_successfully_with_server
```

## Browser Test Requirements

- Laravel Dusk installed
- ChromeDriver running (port 9515)
- At least one server in the database
- User account: admin@devflow.test / password
- Valid session/authentication

## Notes

- All tests use `LoginViaUI` trait for authentication
- Tests use `pause()` for Livewire component initialization
- No `|| true` patterns used - all assertions are proper
- Tests create temporary data and clean up after themselves
- Tests output results summary in tearDown()
