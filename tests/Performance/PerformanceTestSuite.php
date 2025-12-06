<?php

namespace Tests\Performance;

use App\Models\Deployment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceTestSuite extends TestCase
{
    protected array $metrics = [];

    /**
     * Run all performance tests and generate report
     */
    public function runPerformanceTests(): array
    {
        $this->testDatabaseQueryPerformance();
        $this->testCachePerformance();
        $this->testApiResponseTimes();
        $this->testMemoryUsage();
        $this->testConcurrentUserLoad();

        return $this->generatePerformanceReport();
    }

    /**
     * Test database query performance
     */
    protected function test_database_query_performance(): void
    {
        $tests = [
            'simple_select' => function () {
                return DB::table('projects')->limit(100)->get();
            },
            'complex_join' => function () {
                return DB::table('projects')
                    ->join('deployments', 'projects.id', '=', 'deployments.project_id')
                    ->join('servers', 'projects.server_id', '=', 'servers.id')
                    ->select('projects.*', 'deployments.status as deployment_status')
                    ->limit(100)
                    ->get();
            },
            'aggregation' => function () {
                return DB::table('deployments')
                    ->selectRaw('project_id, COUNT(*) as count, AVG(duration_seconds) as avg_duration')
                    ->groupBy('project_id')
                    ->having('count', '>', 5)
                    ->get();
            },
        ];

        foreach ($tests as $name => $test) {
            $startTime = microtime(true);
            $startMemory = memory_get_usage();

            $test();

            $this->metrics['database'][$name] = [
                'time' => round((microtime(true) - $startTime) * 1000, 2), // ms
                'memory' => round((memory_get_usage() - $startMemory) / 1024, 2), // KB
            ];
        }

        // Test N+1 query detection
        $this->detectNPlusOneQueries();
    }

    /**
     * Detect N+1 query problems
     */
    protected function detectNPlusOneQueries(): void
    {
        DB::enableQueryLog();

        // Potentially problematic code
        $projects = Project::limit(10)->get();
        foreach ($projects as $project) {
            $project->deployments->count(); // This could cause N+1
        }

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->metrics['database']['n_plus_one'] = [
            'query_count' => $queryCount,
            'expected' => 2, // Should be 2 queries with eager loading
            'has_n_plus_one' => $queryCount > 5,
        ];
    }

    /**
     * Test cache performance
     */
    protected function test_cache_performance(): void
    {
        $testData = str_repeat('x', 10000); // 10KB of data
        $iterations = 100;

        // Test cache write performance
        $writeStart = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            Cache::put("perf_test_{$i}", $testData, 60);
        }
        $writeTime = microtime(true) - $writeStart;

        // Test cache read performance
        $readStart = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            Cache::get("perf_test_{$i}");
        }
        $readTime = microtime(true) - $readStart;

        // Clean up
        for ($i = 0; $i < $iterations; $i++) {
            Cache::forget("perf_test_{$i}");
        }

        $this->metrics['cache'] = [
            'write_time' => round($writeTime * 1000, 2), // ms
            'read_time' => round($readTime * 1000, 2), // ms
            'write_ops_per_second' => round($iterations / $writeTime),
            'read_ops_per_second' => round($iterations / $readTime),
        ];
    }

    /**
     * Test API response times
     */
    protected function test_api_response_times(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $endpoints = [
            'dashboard' => '/dashboard',
            'projects_list' => '/projects',
            'deployments_list' => '/deployments',
            'analytics' => '/analytics',
        ];

        foreach ($endpoints as $name => $endpoint) {
            $startTime = microtime(true);

            $response = $this->get($endpoint);

            $this->metrics['api'][$name] = [
                'status' => $response->status(),
                'time' => round((microtime(true) - $startTime) * 1000, 2), // ms
                'size' => strlen($response->getContent()), // bytes
            ];
        }
    }

    /**
     * Test memory usage
     */
    protected function test_memory_usage(): void
    {
        $scenarios = [
            'load_100_projects' => function () {
                return Project::limit(100)->with(['deployments', 'domains'])->get();
            },
            'load_1000_deployments' => function () {
                return Deployment::limit(1000)->get();
            },
            'process_large_dataset' => function () {
                $data = [];
                for ($i = 0; $i < 10000; $i++) {
                    $data[] = [
                        'id' => $i,
                        'data' => str_repeat('x', 100),
                    ];
                }

                return collect($data)->map(function ($item) {
                    return array_merge($item, ['processed' => true]);
                })->toArray();
            },
        ];

        foreach ($scenarios as $name => $scenario) {
            $startMemory = memory_get_usage();
            $peakBefore = memory_get_peak_usage();

            $scenario();

            $this->metrics['memory'][$name] = [
                'used' => round((memory_get_usage() - $startMemory) / 1024 / 1024, 2), // MB
                'peak' => round((memory_get_peak_usage() - $peakBefore) / 1024 / 1024, 2), // MB
            ];

            // Force garbage collection
            gc_collect_cycles();
        }

        $this->metrics['memory']['current'] = [
            'usage' => round(memory_get_usage() / 1024 / 1024, 2), // MB
            'peak' => round(memory_get_peak_usage() / 1024 / 1024, 2), // MB
            'limit' => ini_get('memory_limit'),
        ];
    }

    /**
     * Test concurrent user load
     */
    protected function test_concurrent_user_load(): void
    {
        $concurrentUsers = 10;
        $requestsPerUser = 5;

        $startTime = microtime(true);
        $results = [];

        // Simulate concurrent requests
        for ($u = 0; $u < $concurrentUsers; $u++) {
            $user = User::factory()->create();

            for ($r = 0; $r < $requestsPerUser; $r++) {
                $reqStart = microtime(true);

                $this->actingAs($user)->get('/dashboard');

                $results[] = microtime(true) - $reqStart;
            }
        }

        $totalTime = microtime(true) - $startTime;
        $avgResponseTime = array_sum($results) / count($results);

        $this->metrics['load'] = [
            'concurrent_users' => $concurrentUsers,
            'requests_per_user' => $requestsPerUser,
            'total_requests' => $concurrentUsers * $requestsPerUser,
            'total_time' => round($totalTime, 2), // seconds
            'avg_response_time' => round($avgResponseTime * 1000, 2), // ms
            'requests_per_second' => round(($concurrentUsers * $requestsPerUser) / $totalTime, 2),
        ];
    }

    /**
     * Generate performance report
     */
    protected function generatePerformanceReport(): array
    {
        $report = [
            'timestamp' => now()->toIso8601String(),
            'metrics' => $this->metrics,
            'recommendations' => $this->generateRecommendations(),
            'score' => $this->calculatePerformanceScore(),
        ];

        // Save report to file
        $reportPath = storage_path('app/performance-reports/report-'.now()->format('Y-m-d-H-i-s').'.json');
        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));

        return $report;
    }

    /**
     * Generate performance recommendations
     */
    protected function generateRecommendations(): array
    {
        $recommendations = [];

        // Database recommendations
        if ($this->metrics['database']['n_plus_one']['has_n_plus_one'] ?? false) {
            $recommendations[] = [
                'level' => 'critical',
                'area' => 'database',
                'issue' => 'N+1 query problem detected',
                'solution' => 'Use eager loading with with() method',
            ];
        }

        if (($this->metrics['database']['complex_join']['time'] ?? 0) > 100) {
            $recommendations[] = [
                'level' => 'warning',
                'area' => 'database',
                'issue' => 'Slow complex queries detected',
                'solution' => 'Consider adding database indexes or query optimization',
            ];
        }

        // Cache recommendations
        if (($this->metrics['cache']['read_ops_per_second'] ?? 0) < 1000) {
            $recommendations[] = [
                'level' => 'info',
                'area' => 'cache',
                'issue' => 'Cache performance could be improved',
                'solution' => 'Consider using Redis or Memcached instead of file cache',
            ];
        }

        // Memory recommendations
        if (($this->metrics['memory']['peak']['peak'] ?? 0) > 100) {
            $recommendations[] = [
                'level' => 'warning',
                'area' => 'memory',
                'issue' => 'High memory usage detected',
                'solution' => 'Optimize data loading and use chunking for large datasets',
            ];
        }

        // Load recommendations
        if (($this->metrics['load']['avg_response_time'] ?? 0) > 500) {
            $recommendations[] = [
                'level' => 'critical',
                'area' => 'performance',
                'issue' => 'High average response time',
                'solution' => 'Implement caching, optimize queries, and consider scaling',
            ];
        }

        return $recommendations;
    }

    /**
     * Calculate overall performance score
     */
    protected function calculatePerformanceScore(): int
    {
        $score = 100;

        // Deduct points for issues
        if ($this->metrics['database']['n_plus_one']['has_n_plus_one'] ?? false) {
            $score -= 20;
        }

        if (($this->metrics['api']['dashboard']['time'] ?? 0) > 200) {
            $score -= 15;
        }

        if (($this->metrics['memory']['peak']['peak'] ?? 0) > 128) {
            $score -= 10;
        }

        if (($this->metrics['load']['avg_response_time'] ?? 0) > 300) {
            $score -= 15;
        }

        return max(0, $score);
    }
}
