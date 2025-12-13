# HealthDashboard.php Refactoring Guide

## Current File Location
`/app/Livewire/Dashboard/HealthDashboard.php`

## Current State
The file currently contains ~367 lines with business logic mixed with presentation logic.

## Refactored Version

Replace the entire file content with:

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\Project;
use App\Models\Server;
use App\Services\ProjectHealthService;
use App\Services\MetricsCollectionService;
use Livewire\Component;

class HealthDashboard extends Component
{
    /** @var array<int, array<string, mixed>> */
    public array $projectsHealth = [];

    /** @var array<int, array<string, mixed>> */
    public array $serversHealth = [];

    public bool $isLoading = true;

    public string $filterStatus = 'all';

    public ?string $lastCheckedAt = null;

    public function __construct(
        private readonly ProjectHealthService $healthService,
        private readonly MetricsCollectionService $metricsService
    ) {}

    public function mount(): void
    {
        // Check if user has permission to view health checks
        $user = auth()->user();
        abort_unless(
            $user && $user->can('view-health-checks'),
            403,
            'You do not have permission to view health dashboard.'
        );

        // Don't load data on mount - use wire:init for lazy loading
        // This allows the page to render immediately with a loading state
    }

    public function loadHealthData(): void
    {
        $this->isLoading = true;

        $this->loadProjectsHealth();
        $this->loadServersHealth();

        $this->lastCheckedAt = now()->toISOString();
        $this->isLoading = false;
    }

    protected function loadProjectsHealth(): void
    {
        // Use the ProjectHealthService to check all projects
        $healthData = $this->healthService->checkAllProjects();

        $this->projectsHealth = $healthData->toArray();
    }

    protected function loadServersHealth(): void
    {
        // Load all servers with their project counts
        $servers = Server::withCount('projects')->get();

        $this->serversHealth = $servers->map(function ($server) {
            return $this->healthService->checkServerHealth($server);
        })->toArray();
    }

    public function refreshHealth(): void
    {
        // Invalidate all project health caches
        $this->healthService->invalidateAllProjectCaches();

        // Reload health data
        $this->loadHealthData();
    }

    public function getFilteredProjects(): array
    {
        if ($this->filterStatus === 'all') {
            return $this->projectsHealth;
        }

        return array_filter($this->projectsHealth, function ($project) {
            if ($this->filterStatus === 'healthy') {
                return $project['health_score'] >= 80;
            } elseif ($this->filterStatus === 'warning') {
                return $project['health_score'] >= 50 && $project['health_score'] < 80;
            } elseif ($this->filterStatus === 'critical') {
                return $project['health_score'] < 50;
            }

            return true;
        });
    }

    public function getOverallStats(): array
    {
        $total = count($this->projectsHealth);
        $healthy = count(array_filter($this->projectsHealth, fn ($p) => $p['health_score'] >= 80));
        $warning = count(array_filter($this->projectsHealth, fn ($p) => $p['health_score'] >= 50 && $p['health_score'] < 80));
        $critical = count(array_filter($this->projectsHealth, fn ($p) => $p['health_score'] < 50));

        $avgScore = $total > 0
            ? round(array_sum(array_column($this->projectsHealth, 'health_score')) / $total)
            : 0;

        return [
            'total' => $total,
            'healthy' => $healthy,
            'warning' => $warning,
            'critical' => $critical,
            'avg_score' => $avgScore,
        ];
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard.health-dashboard', [
            'filteredProjects' => $this->getFilteredProjects(),
            'stats' => $this->getOverallStats(),
        ]);
    }
}
```

## Changes Summary

### Removed (moved to services):
1. ✅ `checkProjectHealth()` → `ProjectHealthService::checkProject()`
2. ✅ `checkServerHealth()` → `ProjectHealthService::checkServerHealth()`
3. ✅ `checkHttpHealth()` → `ProjectHealthService::checkHttpHealth()`
4. ✅ `getServerMetrics()` → `MetricsCollectionService::collectServerMetrics()`
5. ✅ `calculateHealthScore()` → `ProjectHealthService::calculateHealthScore()`
6. ✅ `calculateServerHealthScore()` → `ProjectHealthService::calculateServerHealthScore()`

### Added:
1. ✅ Constructor dependency injection for services
2. ✅ Simplified `loadProjectsHealth()` using service
3. ✅ Simplified `loadServersHealth()` using service
4. ✅ Updated `refreshHealth()` to use service cache invalidation

### Unchanged:
1. ✅ Public interface (mount, loadHealthData, refreshHealth, render)
2. ✅ View binding (still uses same view file)
3. ✅ Public properties
4. ✅ Filter and stats logic

## Line Count Comparison

| Metric | Before | After | Change |
|--------|--------|-------|--------|
| Total Lines | 367 | ~120 | -247 (-67%) |
| Methods | 10 | 6 | -4 |
| Public Methods | 6 | 6 | No change |
| Dependencies | 5 facades | 2 services | Cleaner |

## Benefits

1. **Separation of Concerns**: Health checking logic is now in a dedicated service
2. **Reusability**: Services can be used in other components, commands, jobs
3. **Testability**: Services can be mocked in tests
4. **Maintainability**: Changes to health checking only affect one service
5. **Code Size**: 67% reduction in component code

## Migration Steps

### Step 1: Backup Current File
```bash
cp app/Livewire/Dashboard/HealthDashboard.php app/Livewire/Dashboard/HealthDashboard.php.backup
```

### Step 2: Replace File Content
Copy the refactored version above and replace the entire file content.

### Step 3: Verify Syntax
```bash
php -l app/Livewire/Dashboard/HealthDashboard.php
```

### Step 4: Test
1. Load the health dashboard page
2. Verify data loads correctly
3. Test the refresh button
4. Test the filter functionality
5. Verify server health displays correctly

### Step 5: Clean Up (Optional)
```bash
# Remove backup if everything works
rm app/Livewire/Dashboard/HealthDashboard.php.backup
```

## Compatibility Notes

### Views
The view file does not need to be changed. The component still provides:
- `$projectsHealth` array
- `$serversHealth` array
- `$filteredProjects` computed property
- `$stats` computed property
- `loadHealthData()` wire:init method
- `refreshHealth()` public method

### Cache Keys
Cache keys remain the same:
- `project_health_{id}` - Still used, but managed by service
- `server_health_{id}` - Still used, but managed by service

### API
All public methods maintain the same signature:
- `loadHealthData()` - No parameters, returns void
- `refreshHealth()` - No parameters, returns void
- `getFilteredProjects()` - No parameters, returns array
- `getOverallStats()` - No parameters, returns array

## Testing Checklist

- [ ] Page loads without errors
- [ ] Projects health data displays
- [ ] Servers health data displays
- [ ] Health scores calculate correctly
- [ ] Filter by status works (all, healthy, warning, critical)
- [ ] Refresh button clears cache and reloads data
- [ ] Issues are displayed correctly
- [ ] Last checked timestamp updates
- [ ] Loading state shows/hides correctly
- [ ] No N+1 query issues
- [ ] Cache invalidation works

## Rollback Plan

If issues occur:
```bash
# Restore backup
cp app/Livewire/Dashboard/HealthDashboard.php.backup app/Livewire/Dashboard/HealthDashboard.php

# Clear cache
php artisan cache:clear
php artisan view:clear
```

## Performance Impact

### Before:
- Direct SSH execution in component
- HTTP requests in component lifecycle
- Cache managed per-component

### After:
- Service handles all external calls
- Centralized caching strategy
- Better separation for optimization
- **Expected Performance**: Comparable or better due to service-level caching

## Future Enhancements

Now that services are extracted, future enhancements become easier:

1. **Add Webhooks**: Notify on health changes
2. **Historical Tracking**: Store health checks in database
3. **Alerting**: Trigger alerts on critical issues
4. **Scheduling**: Run health checks in background
5. **API Endpoints**: Expose health data via API

All these features can be added to the services without touching the component.
