# ServerShow Test - Quick Reference

## File Location
`/home/roshdy/Work/projects/DEVFLOW_PRO/tests/Browser/ServerShowTest.php`

## Stats
- **Total Tests:** 35
- **Total Lines:** 920
- **Component:** ServerShow Livewire
- **Route:** /servers/{server}

## Test Categories

### Navigation (2)
- Page loads successfully
- 404 for non-existent server

### Display Elements (10)
- Server name, IP, status, hostname, port, username
- OS info, CPU/RAM/Disk specs
- Server information section
- Status card with badge

### Links & Buttons (7)
- Edit, Back, Metrics, Security, Backups, Docker, SSL

### Quick Actions (6)
- Quick Actions section, Ping, Reboot, Clear Cache, Services, Docker status

### Data Sections (5)
- Projects list, Metrics, Deployments, Connection status, Resource usage

### Additional (5)
- Flash messages, SSH Terminal, Metrics display, Empty states (2)

## Run Commands

```bash
# All tests
php artisan dusk --filter ServerShowTest

# Specific test
php artisan dusk --filter test_page_loads_successfully_with_server

# By group
php artisan dusk --group server-show
```

## Key Assertions
✅ assertPathIs() - URL verification
✅ assertSee() - Text content
✅ assertSeeLink() - Link presence
✅ assertVisible() - Element visibility
✅ assertTrue() - Custom conditions
✅ markTestSkipped() - Graceful skipping

## No Bad Patterns
❌ No `|| true` patterns
❌ No blind assertions
✅ All proper assertions used
