# Dashboard Tests - Quick Reference Card

## 🚀 Run Tests

```bash
# Run all dashboard tests
php artisan dusk tests/Browser/DashboardTest.php

# Run with interactive menu
./run-dashboard-tests.sh

# Run specific test
php artisan dusk --filter test_dashboard_page_loads_successfully_for_authenticated_user

# Debug mode (visible browser)
DUSK_HEADLESS_DISABLED=true php artisan dusk tests/Browser/DashboardTest.php
```

## 📋 22 Test Cases

| # | Test | What it checks |
|---|------|----------------|
| 1 | `test_dashboard_page_loads_successfully_for_authenticated_user` | Page loads, auth required |
| 2 | `test_stats_cards_are_visible_with_correct_data` | 8 stat cards with data |
| 3 | `test_quick_actions_panel_is_visible_with_all_buttons` | 7 action buttons visible |
| 4 | `test_deploy_all_button_shows_confirmation_and_works` | Deploy confirmation dialog |
| 5 | `test_clear_caches_button_works_and_shows_notification` | Cache clearing works |
| 6 | `test_activity_feed_section_loads_with_recent_activities` | Activity feed populated |
| 7 | `test_server_health_section_shows_server_status` | Server metrics display |
| 8 | `test_deployment_timeline_chart_is_visible` | Timeline chart renders |
| 9 | `test_dashboard_responds_to_dark_light_mode_toggle` | Theme switching |
| 10 | `test_dashboard_widgets_can_be_collapsed_expanded` | Widget collapse/expand |
| 11 | `test_dashboard_auto_refreshes_poll_functionality` | Auto-refresh polling |
| 12 | `test_navigation_links_work_correctly` | Nav links work |
| 13 | `test_user_dropdown_menu_works` | User menu functions |
| 14 | `test_mobile_responsiveness_at_different_viewport_sizes` | 3 viewport sizes |
| 15 | `test_quick_action_links_navigate_to_correct_pages` | Action links work |
| 16 | `test_stats_cards_show_correct_online_offline_counts` | Accurate counts |
| 17 | `test_hero_section_displays_correct_stats` | Hero stats correct |
| 18 | `test_customize_layout_button_toggles_edit_mode` | Layout customization |
| 19 | `test_activity_feed_shows_load_more_button` | Pagination works |
| 20 | `test_dashboard_handles_no_data_gracefully` | Empty states |
| 21 | `test_ssl_expiring_warning_is_displayed` | SSL warnings |
| 22 | `test_deployment_timeline_shows_correct_status_colors` | Status colors |

## 🧪 Test Data Created

- **4 Servers** (3 online, 1 offline)
- **7 Projects** (5 running, 2 stopped)
- **8 Deployments** (5 success, 2 failed, 1 running)
- **4 SSL Certificates** (1 expiring soon)
- **5 Health Checks** (4 healthy, 1 down)

## 🔧 Common Commands

```bash
# Update ChromeDriver
php artisan dusk:chrome-driver --detect

# Check ChromeDriver version
./vendor/laravel/dusk/bin/chromedriver-linux --version

# View screenshots after failure
ls tests/Browser/screenshots/

# Clear old screenshots
rm tests/Browser/screenshots/*.png
```

## 🐛 Quick Debug

```php
// In test method:
$browser->screenshot('debug'); // Take screenshot
$browser->dump(); // Dump HTML
$browser->pause(2000); // Pause 2 seconds
```

## ⚙️ Environment

```bash
# .env.dusk.local
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
DUSK_HEADLESS_DISABLED=false
```

## 📊 Stats Card Coverage

1. ✅ Total Servers
2. ✅ Total Projects
3. ✅ Active Deployments
4. ✅ SSL Certificates
5. ✅ Health Checks
6. ✅ Queue Jobs
7. ✅ Deployments Today
8. ✅ Security Score

## 🎯 Quick Action Coverage

1. ✅ New Project
2. ✅ Add Server
3. ✅ Deploy All
4. ✅ Clear Caches
5. ✅ View Logs
6. ✅ Health Checks
7. ✅ Settings

## 📱 Responsive Breakpoints

- **Mobile:** 375x667 (iPhone SE)
- **Tablet:** 768x1024 (iPad)
- **Desktop:** 1920x1080

## ⏱️ Execution Time

- Single test: ~5-8 seconds
- Full suite: ~2-3 minutes (headless)
- With visible browser: ~3-4 minutes

## 🚦 Exit Codes

- **0** = All tests passed
- **1** = One or more tests failed
- **2** = Error during execution

## 📝 Before Committing

```bash
# Run all tests
php artisan dusk tests/Browser/DashboardTest.php

# Check syntax
php -l tests/Browser/DashboardTest.php

# Verify no screenshots (all passed)
ls tests/Browser/screenshots/
```

## 🔗 Links

- Full docs: `tests/Browser/README.md`
- Detailed specs: `DASHBOARD_TESTS.md`
- Laravel Dusk: https://laravel.com/docs/dusk
