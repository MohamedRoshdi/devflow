# Services Quick Reference Guide

## ProjectHealthService

### Import
```php
use App\Services\ProjectHealthService;
```

### Dependency Injection
```php
public function __construct(
    private readonly ProjectHealthService $healthService
) {}
```

### Common Methods

#### Check All Projects
```php
$healthData = $this->healthService->checkAllProjects();
// Returns: Collection of health check results
```

#### Check Single Project
```php
$result = $this->healthService->checkProject($project);
// Returns: [
//     'id' => int,
//     'name' => string,
//     'status' => 'healthy'|'warning'|'critical',
//     'health_score' => 0-100,
//     'checks' => [...],
//     'issues' => [...]
// ]
```

#### Check Server Health
```php
$result = $this->healthService->checkServerHealth($server);
// Returns: Similar structure to project health
```

#### Invalidate Cache
```php
$this->healthService->invalidateProjectCache($project);
$this->healthService->invalidateAllProjectCaches();
```

---

## MetricsCollectionService

### Import
```php
use App\Services\MetricsCollectionService;
```

### Dependency Injection
```php
public function __construct(
    private readonly MetricsCollectionService $metricsService
) {}
```

### Common Methods

#### Collect Server Metrics
```php
$metrics = $this->metricsService->collectServerMetrics($server);
// Returns: [
//     'cpu' => float|null,
//     'memory' => ['usage_percent' => float, 'used_mb' => int, ...],
//     'disk' => ['usage_percent' => float, 'used_gb' => float, ...],
//     'network' => ['in_bytes' => int, 'out_bytes' => int, ...],
//     'load' => ['load_1' => float, 'load_5' => float, ...],
//     'uptime' => string|null
// ]
```

#### Collect All Server Metrics
```php
$allMetrics = $this->metricsService->collectAllServerMetrics();
// Returns: Array keyed by server ID
```

#### Get Health Metrics
```php
$health = $this->metricsService->getServerHealthMetrics($server);
// Returns: [
//     'cpu' => float,
//     'memory' => float,
//     'disk' => float,
//     'load' => float,
//     'status' => 'healthy'|'warning'|'critical'
// ]
```

#### Get Formatted Dashboard Metrics
```php
$formatted = $this->metricsService->getFormattedMetricsForDashboard();
// Returns: Array of formatted metrics ready for display
```

---

## CacheManagementService

### Import
```php
use App\Services\CacheManagementService;
```

### Dependency Injection
```php
public function __construct(
    private readonly CacheManagementService $cacheService
) {}
```

### Common Methods

#### Clear All Caches
```php
$result = $this->cacheService->clearAllCaches();
// Returns: ['app' => bool, 'config' => bool, 'route' => bool, ...]

$result = $this->cacheService->clearAllCachesComplete();
// Returns: ['cleared' => array, 'failed' => array]
```

#### Remember with Different TTLs
```php
// Default TTL (5 minutes)
$data = $this->cacheService->remember('key', function() {
    return expensive_operation();
});

// Short TTL (30 seconds) - for frequently changing data
$metrics = $this->cacheService->rememberShort('metrics', function() {
    return get_current_metrics();
});

// Long TTL (1 hour) - for rarely changing data
$stats = $this->cacheService->rememberLong('ssl_stats', function() {
    return get_ssl_statistics();
});
```

#### Custom TTL
```php
$data = $this->cacheService->remember('key', function() {
    return data();
}, 600); // 10 minutes
```

#### Invalidate Caches
```php
// Single key
$this->cacheService->forget('key');

// Multiple keys
$this->cacheService->forgetMultiple(['key1', 'key2', 'key3']);

// Dashboard caches
$this->cacheService->invalidateDashboardCache();

// Project-specific
$this->cacheService->invalidateProjectCache($projectId);

// Server-specific
$this->cacheService->invalidateServerCache($serverId);
```

#### Cache with Tags (Redis/Memcached only)
```php
$data = $this->cacheService->rememberWithTags(
    ['projects', 'deployments'],
    'project_deployments',
    function() {
        return get_deployments();
    }
);

// Flush all tags
$this->cacheService->flushTags(['projects', 'deployments']);
```

#### Clear Project Cache (Docker)
```php
$success = $this->cacheService->clearProjectCache($project);
```

#### Cache Statistics
```php
$stats = $this->cacheService->getCacheStats();
// Returns: [
//     'driver' => 'redis',
//     'supported_features' => [
//         'tagging' => true,
//         'persistence' => true,
//         'prefix_invalidation' => true
//     ]
// ]
```

#### Cache Warming
```php
$result = $this->cacheService->warmUpCache();
// Pre-populates commonly used cache data
```

---

## Usage Examples

### In a Livewire Component

```php
<?php

namespace App\Livewire;

use App\Services\ProjectHealthService;
use App\Services\MetricsCollectionService;
use App\Services\CacheManagementService;
use Livewire\Component;

class MyDashboard extends Component
{
    public function __construct(
        private readonly ProjectHealthService $healthService,
        private readonly MetricsCollectionService $metricsService,
        private readonly CacheManagementService $cacheService
    ) {}

    public function loadData()
    {
        // Check project health with caching
        $health = $this->cacheService->rememberShort(
            "project_health_{$this->projectId}",
            fn() => $this->healthService->checkProject($this->project)
        );

        // Get server metrics
        $metrics = $this->metricsService->collectServerMetrics($this->server);

        return compact('health', 'metrics');
    }

    public function refreshData()
    {
        // Invalidate caches
        $this->cacheService->invalidateProjectCache($this->projectId);
        $this->healthService->invalidateProjectCache($this->project);

        // Reload data
        $this->loadData();
    }
}
```

### In an Artisan Command

```php
<?php

namespace App\Console\Commands;

use App\Services\ProjectHealthService;
use App\Services\MetricsCollectionService;
use Illuminate\Console\Command;

class CheckSystemHealth extends Command
{
    public function __construct(
        private readonly ProjectHealthService $healthService,
        private readonly MetricsCollectionService $metricsService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Checking all projects...');

        $projects = $this->healthService->checkAllProjects();

        foreach ($projects as $project) {
            $status = $project['status'];
            $score = $project['health_score'];
            $this->line("{$project['name']}: {$status} (Score: {$score})");

            if ($score < 50) {
                $this->error("Issues: " . implode(', ', $project['issues']));
            }
        }

        $this->info('Done!');
    }
}
```

### In a Controller

```php
<?php

namespace App\Http\Controllers;

use App\Services\ProjectHealthService;
use App\Services\CacheManagementService;
use App\Models\Project;

class HealthController extends Controller
{
    public function __construct(
        private readonly ProjectHealthService $healthService,
        private readonly CacheManagementService $cacheService
    ) {}

    public function show(Project $project)
    {
        $health = $this->cacheService->remember(
            "api_project_health_{$project->id}",
            fn() => $this->healthService->checkProject($project),
            60 // Cache for 1 minute
        );

        return response()->json($health);
    }

    public function refresh(Project $project)
    {
        $this->healthService->invalidateProjectCache($project);

        $health = $this->healthService->checkProject($project);

        return response()->json($health);
    }
}
```

### In a Job

```php
<?php

namespace App\Jobs;

use App\Services\ProjectHealthService;
use App\Services\MetricsCollectionService;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class MonitorProjectHealth implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Project $project
    ) {}

    public function handle(
        ProjectHealthService $healthService,
        MetricsCollectionService $metricsService
    ) {
        $health = $healthService->checkProject($this->project);

        if ($health['status'] === 'critical') {
            // Send alert
            \Log::critical("Project {$this->project->name} is in critical state", [
                'health_score' => $health['health_score'],
                'issues' => $health['issues']
            ]);
        }

        // Store metrics if needed
        $metrics = $metricsService->collectServerMetrics($this->project->server);
        // Save to database...
    }
}
```

---

## Best Practices

### 1. Always Use Dependency Injection
```php
// Good
public function __construct(
    private readonly ProjectHealthService $healthService
) {}

// Avoid
$healthService = new ProjectHealthService();
$healthService = app(ProjectHealthService::class);
```

### 2. Cache Appropriately
```php
// Frequently changing data (metrics, queue stats)
$data = $cacheService->rememberShort('key', $callback);

// Normal data (most cases)
$data = $cacheService->remember('key', $callback);

// Rarely changing data (SSL stats, security scores)
$data = $cacheService->rememberLong('key', $callback);
```

### 3. Invalidate Cache When Needed
```php
// After deployment
$cacheService->invalidateProjectCache($project->id);
$healthService->invalidateProjectCache($project);

// After server update
$cacheService->invalidateServerCache($server->id);
```

### 4. Handle Errors Gracefully
```php
try {
    $health = $healthService->checkProject($project);
} catch (\Exception $e) {
    \Log::error("Failed to check project health: {$e->getMessage()}");
    // Return default/fallback data
    $health = ['status' => 'unknown', 'health_score' => 0];
}
```

### 5. Use Type Hints
```php
// All methods use strict type hints
function processHealth(array $health): void
{
    // Type-safe operations
}
```

---

## TTL Reference

| Use Case | TTL | Method |
|----------|-----|--------|
| Metrics, Queue Stats | 30s | `rememberShort()` |
| Project Health | 60s | `remember('key', $cb, 60)` |
| Dashboard Stats | 5min | `remember()` (default) |
| SSL Stats, Security Scores | 1hr | `rememberLong()` |
| Onboarding Status | 5min | `remember('key', $cb, 300)` |

---

## Health Score Thresholds

| Score Range | Status | Color |
|-------------|--------|-------|
| 80-100 | Healthy | Green |
| 50-79 | Warning | Yellow |
| 0-49 | Critical | Red |

---

## Metric Thresholds

| Metric | Warning | Critical |
|--------|---------|----------|
| CPU Usage | > 75% | > 90% |
| Memory Usage | > 75% | > 90% |
| Disk Usage | > 75% | > 90% |
| Response Time | > 1s | > 2s |

---

## Service Dependencies

```
ProjectHealthService
├── DockerService (constructor injection)
└── Uses: Cache, Http facades

MetricsCollectionService
├── No service dependencies
└── Uses: Process facade

CacheManagementService
├── DockerService (constructor injection)
└── Uses: Cache, Artisan facades
```
