# DevFlow Pro - Dashboard Browser Tests Documentation

## Overview

Comprehensive Laravel Dusk browser test suite for the DevFlow Pro Dashboard, covering all critical functionality, UI/UX interactions, and responsive design.

**Test File:** `tests/Browser/DashboardTest.php`
**Total Tests:** 22 comprehensive test cases
**Framework:** Laravel Dusk 8.x
**PHP Version:** 8.4
**Laravel Version:** 12.x

---

## 📋 Test Coverage Summary

### Core Functionality (8 tests)
- ✅ Dashboard page authentication and loading
- ✅ Stats cards data accuracy and visibility
- ✅ Quick Actions panel functionality
- ✅ Deploy All with confirmation dialog
- ✅ Clear Caches functionality
- ✅ Activity feed with recent activities
- ✅ Server health metrics display
- ✅ Deployment timeline visualization

### UI/UX Interactions (6 tests)
- ✅ Dark/Light mode theme toggle
- ✅ Widget collapse/expand functionality
- ✅ Auto-refresh polling
- ✅ Navigation links
- ✅ User dropdown menu
- ✅ Mobile responsiveness (375px, 768px, 1920px)

### Advanced Features (8 tests)
- ✅ Quick action navigation
- ✅ Stats accuracy (online/offline counts)
- ✅ Hero section statistics
- ✅ Customize Layout edit mode
- ✅ Activity feed Load More
- ✅ Empty state handling
- ✅ SSL expiration warnings
- ✅ Deployment status colors

---

## 🚀 Quick Start

### 1. Run All Tests
```bash
./run-dashboard-tests.sh
```

Or directly:
```bash
php artisan dusk tests/Browser/DashboardTest.php
```

### 2. Run Specific Test
```bash
php artisan dusk --filter test_dashboard_page_loads_successfully_for_authenticated_user
```

### 3. Debug Mode (Visible Browser)
```bash
DUSK_HEADLESS_DISABLED=true php artisan dusk tests/Browser/DashboardTest.php
```

---

## 📊 Test Data Structure

Each test automatically creates:

| Resource | Quantity | Details |
|----------|----------|---------|
| **Servers** | 4 | 3 online, 1 offline |
| **Projects** | 7 | 5 running, 2 stopped |
| **Deployments** | 8 | 5 success, 2 failed, 1 running |
| **SSL Certs** | 4 | 3 valid, 1 expiring soon |
| **Health Checks** | 5 | 4 healthy, 1 down |
| **Server Metrics** | 3 | CPU, Memory, Disk usage |

All data is created using Laravel factories and automatically cleaned up after each test.

---

## 🧪 Detailed Test Cases

### Test 1: Dashboard Page Loads
**Purpose:** Verify authenticated users can access dashboard
**Assertions:**
- URL path is `/dashboard`
- "Welcome Back!" text visible
- Infrastructure overview text present
- "DevFlow Pro" branding visible

**Test Method:** `test_dashboard_page_loads_successfully_for_authenticated_user()`

---

### Test 2: Stats Cards Visibility
**Purpose:** Ensure all 8 stat cards display correct data
**Cards Tested:**
1. Total Servers (4 total)
2. Total Projects (7 total)
3. Active Deployments (1 running)
4. SSL Certificates (4 active)
5. Health Checks (4 healthy)
6. Queue Jobs
7. Deployments Today (8 total)
8. Security Score

**Test Method:** `test_stats_cards_are_visible_with_correct_data()`

---

### Test 3: Quick Actions Panel
**Purpose:** Verify all quick action buttons are present and visible
**Actions Tested:**
- New Project
- Add Server
- Deploy All
- Clear Caches
- View Logs
- Health Checks
- Settings

**Test Method:** `test_quick_actions_panel_is_visible_with_all_buttons()`

---

### Test 4: Deploy All Functionality
**Purpose:** Test Deploy All button with confirmation dialog
**Flow:**
1. Click "Deploy All" button
2. Wait for confirmation dialog
3. Accept dialog
4. Verify notification appears
5. Check for "Deploying" message

**Test Method:** `test_deploy_all_button_shows_confirmation_and_works()`

---

### Test 5: Clear Caches
**Purpose:** Test cache clearing functionality
**Flow:**
1. Click "Clear Caches" button
2. Wait for Livewire action
3. Verify success notification
4. Check for "cleared" message

**Test Method:** `test_clear_caches_button_works_and_shows_notification()`

---

### Test 6: Activity Feed
**Purpose:** Verify recent activity section loads and displays activities
**Checks:**
- "Recent Activity" heading
- "Auto-refresh" indicator
- Deployment activities listed
- User attribution ("by [user]")
- Activity list elements present

**Test Method:** `test_activity_feed_section_loads_with_recent_activities()`

---

### Test 7: Server Health
**Purpose:** Ensure server health metrics are displayed
**Metrics:**
- CPU usage
- Memory usage
- Disk usage
- Server names
- Health status indicators

**Test Method:** `test_server_health_section_shows_server_status()`

---

### Test 8: Deployment Timeline
**Purpose:** Verify deployment timeline chart displays correctly
**Elements:**
- "Deployment Timeline (Last 7 Days)" heading
- Success bar (green)
- Failed bar (red)
- Legend with labels
- Daily data points

**Test Method:** `test_deployment_timeline_chart_is_visible()`

---

### Test 9: Dark/Light Mode Toggle
**Purpose:** Test theme switching functionality
**Flow:**
1. Find theme toggle button
2. Click to toggle theme
3. Verify dark class on HTML element
4. Confirm theme change applied

**Test Method:** `test_dashboard_responds_to_dark_light_mode_toggle()`

---

### Test 10: Widget Collapse/Expand
**Purpose:** Test collapsible sections functionality
**Flow:**
1. Click collapse button on Deployment Timeline
2. Wait for section to collapse
3. Verify content hidden
4. Click to expand
5. Verify content visible again

**Test Method:** `test_dashboard_widgets_can_be_collapsed_expanded()`

---

### Test 11: Auto-Refresh Polling
**Purpose:** Verify dashboard auto-refresh functionality
**Checks:**
- `wire:poll` attribute present
- Poll interval set to 30 seconds

**Test Method:** `test_dashboard_auto_refreshes_poll_functionality()`

---

### Test 12: Navigation Links
**Purpose:** Test main navigation functionality
**Links Tested:**
- Servers → `/servers`
- Dashboard → `/dashboard`
- Projects → `/projects`

**Test Method:** `test_navigation_links_work_correctly()`

---

### Test 13: User Dropdown Menu
**Purpose:** Verify user menu functionality
**Checks:**
- Dropdown button clickable
- Dropdown menu appears
- Menu items accessible

**Test Method:** `test_user_dropdown_menu_works()`

---

### Test 14: Mobile Responsiveness
**Purpose:** Test dashboard at different viewport sizes
**Viewports:**
1. **Mobile:** 375x667 (iPhone SE)
2. **Tablet:** 768x1024 (iPad)
3. **Desktop:** 1920x1080

**Elements Verified:**
- Welcome message
- Stats cards
- Quick actions
- Activity feed
- Deployment timeline
- Server health

**Test Method:** `test_mobile_responsiveness_at_different_viewport_sizes()`

---

### Test 15: Quick Action Navigation
**Purpose:** Verify quick action links navigate correctly
**Links Tested:**
- New Project → `/projects/create`
- Add Server → `/servers/create`
- View Logs → `/logs`

**Test Method:** `test_quick_action_links_navigate_to_correct_pages()`

---

### Test 16: Stats Card Accuracy
**Purpose:** Verify online/offline counts are correct
**Checks:**
- Server stats: "3 online, 1 offline"
- Project stats: "5 running"

**Test Method:** `test_stats_cards_show_correct_online_offline_counts()`

---

### Test 17: Hero Section Stats
**Purpose:** Verify hero section displays correct information
**Elements:**
- "Servers Online" stat
- "Running Projects" stat
- "Deployments Today" stat
- Gradient background

**Test Method:** `test_hero_section_displays_correct_stats()`

---

### Test 18: Customize Layout
**Purpose:** Test dashboard customization mode
**Flow:**
1. Click "Customize Layout" button
2. Verify edit mode activated
3. Check for "Edit Mode" message
4. Verify "Reset Layout" button appears
5. Click "Done" to exit
6. Verify edit mode deactivated

**Test Method:** `test_customize_layout_button_toggles_edit_mode()`

---

### Test 19: Activity Feed Load More
**Purpose:** Test activity pagination
**Checks:**
- Activity feed section present
- Load More button (if applicable)
- Activity items displayed

**Test Method:** `test_activity_feed_shows_load_more_button()`

---

### Test 20: Empty State Handling
**Purpose:** Verify dashboard handles no data gracefully
**Flow:**
1. Truncate all test data
2. Reload dashboard
3. Verify zero stats displayed
4. Check for empty state messages:
   - "No recent activity"
   - "No servers online"

**Test Method:** `test_dashboard_handles_no_data_gracefully()`

---

### Test 21: SSL Expiration Warning
**Purpose:** Test SSL certificate expiration alerts
**Checks:**
- Expiring soon count displayed (1 expiring)
- Warning color applied (amber)
- Certificate count accurate

**Test Method:** `test_ssl_expiring_warning_is_displayed()`

---

### Test 22: Deployment Status Colors
**Purpose:** Verify deployment timeline uses correct status colors
**Colors:**
- Success: Green (`from-emerald-500`)
- Failed: Red (`from-red-500`)
- Legend present with labels

**Test Method:** `test_deployment_timeline_shows_correct_status_colors()`

---

## 🛠️ Test Configuration

### Environment Setup

**File:** `.env.dusk.local`

```env
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

# For debugging with visible browser
DUSK_HEADLESS_DISABLED=false
```

### ChromeDriver Configuration

```bash
# Auto-detect and install correct version
php artisan dusk:chrome-driver --detect

# Manual version installation
php artisan dusk:chrome-driver 128
```

---

## 🐛 Debugging

### Enable Screenshots
Screenshots automatically captured on failure:
```
tests/Browser/screenshots/
```

### Manual Screenshot
```php
$browser->screenshot('debug-name');
```

### Browser Console Logs
```php
$browser->dump(); // Dump current page HTML
```

### Slow Down Test Execution
```php
$browser->pause(2000); // Pause for 2 seconds
```

### Run Single Test with Output
```bash
php artisan dusk --filter test_name --debug
```

---

## 📈 Performance Metrics

| Metric | Value |
|--------|-------|
| Total Tests | 22 |
| Average Test Time | 5-8 seconds |
| Full Suite Time (Headless) | 2-3 minutes |
| Full Suite Time (Visible) | 3-4 minutes |
| With Database Seeding | +30-60 seconds |

---

## 🔧 Troubleshooting

### Common Issues

#### 1. ChromeDriver Version Mismatch
**Error:** "Session not created: This version of ChromeDriver only supports Chrome version X"

**Solution:**
```bash
php artisan dusk:chrome-driver --detect
```

#### 2. Database Errors
**Error:** "Database table not found"

**Solution:**
```bash
php artisan migrate --database=testing
```

#### 3. Timeout Errors
**Error:** "Element not found after X seconds"

**Solution:** Increase wait times in test:
```php
->waitFor('.selector', 10) // Wait up to 10 seconds
```

#### 4. Livewire Not Responding
**Error:** "Livewire component did not update"

**Solution:**
```php
->waitForLivewire() // Wait for Livewire to finish processing
->pause(1000) // Add pause after Livewire action
```

---

## 🚦 CI/CD Integration

### GitHub Actions Workflow

```yaml
name: Dashboard Tests

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

      - name: Install Dependencies
        run: composer install

      - name: Install ChromeDriver
        run: php artisan dusk:chrome-driver --detect

      - name: Run Tests
        run: php artisan dusk tests/Browser/DashboardTest.php

      - name: Upload Screenshots
        if: failure()
        uses: actions/upload-artifact@v2
        with:
          name: screenshots
          path: tests/Browser/screenshots
```

---

## 📚 Additional Resources

- **Laravel Dusk Documentation:** https://laravel.com/docs/dusk
- **Livewire Testing:** https://livewire.laravel.com/docs/testing
- **ChromeDriver Downloads:** https://chromedriver.chromium.org/downloads
- **Test README:** `tests/Browser/README.md`

---

## 👥 Contributing

When adding new dashboard features:

1. ✅ Add corresponding test to `DashboardTest.php`
2. ✅ Follow naming convention: `test_feature_description()`
3. ✅ Add proper docblocks
4. ✅ Test mobile responsiveness
5. ✅ Include both success and failure scenarios
6. ✅ Update this documentation

---

## 📝 Notes

- All tests use `DatabaseMigrations` for automatic cleanup
- Test data is created using factories for consistency
- Tests are designed to be independent and run in any order
- Livewire polling is tested but actual refresh behavior may vary
- Mobile tests verify layout at standard device sizes

---

## ✅ Test Checklist

Before deploying dashboard changes:

- [ ] All 22 tests pass
- [ ] New features have corresponding tests
- [ ] Tests pass on mobile viewports
- [ ] Dark mode tested
- [ ] No console errors
- [ ] Screenshots reviewed (if any failures)
- [ ] ChromeDriver version updated
- [ ] Documentation updated

---

**Last Updated:** December 5, 2024
**Test Suite Version:** 1.0.0
**Maintained By:** DevFlow Pro Team
