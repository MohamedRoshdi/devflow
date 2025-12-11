<?php

namespace Tests\Performance;

use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceTestSuite extends TestCase
{
    protected static array $metrics = [];

    /**
     * Run all performance tests and return results
     *
     * @return array{metrics: array, recommendations: array, score: int}
     */
    public function runPerformanceTests(): array
    {
        $this->test_database_query_performance();
        $this->test_cache_performance();
        $this->test_memory_usage();

        return [
            'metrics' => self::$metrics,
            'recommendations' => self::generateRecommendationsStatic(),
            'score' => self::calculatePerformanceScoreStatic(),
        ];
    }

    /**
     * Test database query performance
     */
    public function test_database_query_performance(): void
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

            self::$metrics['database'][$name] = [
                'time' => round((microtime(true) - $startTime) * 1000, 2), // ms
                'memory' => round((memory_get_usage() - $startMemory) / 1024, 2), // KB
            ];

            // Assert reasonable performance thresholds
            $this->assertLessThan(1000, self::$metrics['database'][$name]['time'],
                "Query {$name} took too long: " . self::$metrics['database'][$name]['time'] . "ms");
        }

        $this->assertTrue(true, 'Database query performance tests passed');
    }

    /**
     * Test N+1 query detection
     */
    public function test_n_plus_one_query_detection(): void
    {
        // Create test data
        $server = Server::factory()->create();
        $projects = Project::factory()->count(10)->create(['server_id' => $server->id]);

        foreach ($projects as $project) {
            Deployment::factory()->count(3)->create(['project_id' => $project->id]);
        }

        DB::enableQueryLog();

        // Test without eager loading (should trigger N+1)
        $projectsWithoutEager = Project::limit(10)->get();
        foreach ($projectsWithoutEager as $project) {
            $project->deployments->count();
        }

        $queryCountWithoutEager = count(DB::getQueryLog());
        DB::flushQueryLog();

        // Test with eager loading (should be optimized)
        $projectsWithEager = Project::with('deployments')->limit(10)->get();
        foreach ($projectsWithEager as $project) {
            $project->deployments->count();
        }

        $queryCountWithEager = count(DB::getQueryLog());
        DB::disableQueryLog();

        self::$metrics['database']['n_plus_one'] = [
            'without_eager' => $queryCountWithoutEager,
            'with_eager' => $queryCountWithEager,
            'has_n_plus_one' => $queryCountWithoutEager > ($queryCountWithEager * 2),
        ];

        // Assert eager loading is more efficient
        $this->assertLessThan($queryCountWithoutEager, $queryCountWithEager * 2,
            'Eager loading should significantly reduce queries');
    }

    /**
     * Test cache performance
     */
    public function test_cache_performance(): void
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

        self::$metrics['cache'] = [
            'write_time' => round($writeTime * 1000, 2), // ms
            'read_time' => round($readTime * 1000, 2), // ms
            'write_ops_per_second' => round($iterations / $writeTime),
            'read_ops_per_second' => round($iterations / $readTime),
        ];

        // Assert reasonable cache performance
        $this->assertGreaterThan(0, self::$metrics['cache']['write_ops_per_second'],
            'Cache write operations per second should be greater than 0');
        $this->assertGreaterThan(0, self::$metrics['cache']['read_ops_per_second'],
            'Cache read operations per second should be greater than 0');
    }

    /**
     * Test API response times
     */
    public function test_api_response_times(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $endpoints = [
            'dashboard' => '/dashboard',
            'projects_list' => '/projects',
            'deployments_list' => '/deployments',
        ];

        foreach ($endpoints as $name => $endpoint) {
            $startTime = microtime(true);

            $response = $this->get($endpoint);

            self::$metrics['api'][$name] = [
                'status' => $response->status(),
                'time' => round((microtime(true) - $startTime) * 1000, 2), // ms
                'size' => strlen($response->getContent()), // bytes
            ];

            // Assert successful response
            $this->assertEquals(200, self::$metrics['api'][$name]['status'],
                "Endpoint {$name} should return 200 status");

            // Assert reasonable response time (under 2 seconds for tests)
            $this->assertLessThan(2000, self::$metrics['api'][$name]['time'],
                "Endpoint {$name} took too long: " . self::$metrics['api'][$name]['time'] . "ms");
        }
    }

    /**
     * Test memory usage
     */
    public function test_memory_usage(): void
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

            self::$metrics['memory'][$name] = [
                'used' => round((memory_get_usage() - $startMemory) / 1024 / 1024, 2), // MB
                'peak' => round((memory_get_peak_usage() - $peakBefore) / 1024 / 1024, 2), // MB
            ];

            // Assert memory usage is reasonable (under 256MB for tests)
            $this->assertLessThan(256, self::$metrics['memory'][$name]['peak'],
                "Scenario {$name} used too much memory: " . self::$metrics['memory'][$name]['peak'] . "MB");

            // Force garbage collection
            gc_collect_cycles();
        }

        self::$metrics['memory']['current'] = [
            'usage' => round(memory_get_usage() / 1024 / 1024, 2), // MB
            'peak' => round(memory_get_peak_usage() / 1024 / 1024, 2), // MB
            'limit' => ini_get('memory_limit'),
        ];
    }

    /**
     * Test concurrent user load
     */
    public function test_concurrent_user_load(): void
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

        self::$metrics['load'] = [
            'concurrent_users' => $concurrentUsers,
            'requests_per_user' => $requestsPerUser,
            'total_requests' => $concurrentUsers * $requestsPerUser,
            'total_time' => round($totalTime, 2), // seconds
            'avg_response_time' => round($avgResponseTime * 1000, 2), // ms
            'requests_per_second' => round(($concurrentUsers * $requestsPerUser) / $totalTime, 2),
        ];

        // Assert reasonable load performance (under 5 seconds average for tests)
        $this->assertLessThan(5000, self::$metrics['load']['avg_response_time'],
            'Average response time under load is too high: ' . self::$metrics['load']['avg_response_time'] . 'ms');
    }

    /**
     * Generate and save performance report after all tests
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        if (empty(self::$metrics)) {
            return;
        }

        $report = [
            'timestamp' => now()->toIso8601String(),
            'metrics' => self::$metrics,
            'recommendations' => self::generateRecommendationsStatic(),
            'score' => self::calculatePerformanceScoreStatic(),
        ];

        // Save report to file
        $reportDir = storage_path('app/performance-reports');
        if (!is_dir($reportDir)) {
            mkdir($reportDir, 0755, true);
        }

        $reportPath = $reportDir . '/report-' . now()->format('Y-m-d-H-i-s') . '.json';
        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
    }

    /**
     * Generate performance recommendations
     */
    protected static function generateRecommendationsStatic(): array
    {
        $recommendations = [];

        // Database recommendations
        if (self::$metrics['database']['n_plus_one']['has_n_plus_one'] ?? false) {
            $recommendations[] = [
                'level' => 'critical',
                'area' => 'database',
                'issue' => 'N+1 query problem detected',
                'solution' => 'Use eager loading with with() method',
            ];
        }

        if ((self::$metrics['database']['complex_join']['time'] ?? 0) > 100) {
            $recommendations[] = [
                'level' => 'warning',
                'area' => 'database',
                'issue' => 'Slow complex queries detected',
                'solution' => 'Consider adding database indexes or query optimization',
            ];
        }

        // Cache recommendations
        if ((self::$metrics['cache']['read_ops_per_second'] ?? 0) < 1000) {
            $recommendations[] = [
                'level' => 'info',
                'area' => 'cache',
                'issue' => 'Cache performance could be improved',
                'solution' => 'Consider using Redis or Memcached instead of file cache',
            ];
        }

        // Memory recommendations
        foreach (self::$metrics['memory'] ?? [] as $name => $metrics) {
            if (isset($metrics['peak']) && $metrics['peak'] > 100) {
                $recommendations[] = [
                    'level' => 'warning',
                    'area' => 'memory',
                    'issue' => "High memory usage in {$name}: {$metrics['peak']}MB",
                    'solution' => 'Optimize data loading and use chunking for large datasets',
                ];
            }
        }

        // Load recommendations
        if ((self::$metrics['load']['avg_response_time'] ?? 0) > 500) {
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
    protected static function calculatePerformanceScoreStatic(): int
    {
        $score = 100;

        // Deduct points for issues
        if (self::$metrics['database']['n_plus_one']['has_n_plus_one'] ?? false) {
            $score -= 20;
        }

        if ((self::$metrics['api']['dashboard']['time'] ?? 0) > 200) {
            $score -= 15;
        }

        foreach (self::$metrics['memory'] ?? [] as $metrics) {
            if (isset($metrics['peak']) && $metrics['peak'] > 128) {
                $score -= 10;
                break;
            }
        }

        if ((self::$metrics['load']['avg_response_time'] ?? 0) > 300) {
            $score -= 15;
        }

        return max(0, $score);
    }
}
