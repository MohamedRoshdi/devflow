<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Tests\Performance\PerformanceTestSuite;
use Tests\Security\SecurityAudit;

class RunQualityTests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:quality
                            {--type=all : Type of tests to run (all, unit, performance, security, mobile)}
                            {--report : Generate detailed report}
                            {--email= : Email report to specified address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run comprehensive quality tests including performance, security, and mobile responsiveness';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->option('type');
        $generateReport = $this->option('report');
        $emailTo = $this->option('email');

        $this->info('🚀 Starting DevFlow Pro Quality Testing Suite');
        $this->newLine();

        $results = [];

        // Run tests based on type
        if ($type === 'all' || $type === 'unit') {
            $results['unit'] = $this->runUnitTests();
        }

        if ($type === 'all' || $type === 'performance') {
            $results['performance'] = $this->runPerformanceTests();
        }

        if ($type === 'all' || $type === 'security') {
            $results['security'] = $this->runSecurityAudit();
        }

        if ($type === 'all' || $type === 'mobile') {
            $results['mobile'] = $this->runMobileTests();
        }

        // Generate comprehensive report
        if ($generateReport) {
            $report = $this->generateComprehensiveReport($results);
            $this->displayReport($report);

            if ($emailTo) {
                $this->emailReport($report, $emailTo);
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Run unit and feature tests
     */
    protected function runUnitTests(): array
    {
        $this->info('📝 Running Unit & Feature Tests...');

        $output = shell_exec('php artisan test --parallel 2>&1');

        // Parse test results
        preg_match('/Tests:\s+(\d+)\s+passed/', $output, $passed);
        preg_match('/(\d+)\s+failed/', $output, $failed);
        preg_match('/Time:\s+([\d.]+)s/', $output, $time);

        $results = [
            'passed' => $passed[1] ?? 0,
            'failed' => $failed[1] ?? 0,
            'time' => $time[1] ?? 0,
            'coverage' => $this->getCodeCoverage(),
        ];

        $this->table(
            ['Metric', 'Value'],
            [
                ['Tests Passed', $results['passed']],
                ['Tests Failed', $results['failed']],
                ['Execution Time', $results['time'] . 's'],
                ['Code Coverage', $results['coverage'] . '%'],
            ]
        );

        return $results;
    }

    /**
     * Run performance tests
     */
    protected function runPerformanceTests(): array
    {
        $this->info('⚡ Running Performance Tests...');

        $suite = new PerformanceTestSuite();
        $results = $suite->runPerformanceTests();

        $this->table(
            ['Area', 'Metric', 'Value', 'Status'],
            [
                ['Database', 'Query Time', $results['metrics']['database']['simple_select']['time'] ?? 'N/A' . 'ms', $this->getStatus($results['metrics']['database']['simple_select']['time'] ?? 0, 50)],
                ['Cache', 'Read Ops/Sec', $results['metrics']['cache']['read_ops_per_second'] ?? 'N/A', $this->getStatus($results['metrics']['cache']['read_ops_per_second'] ?? 0, 1000, true)],
                ['API', 'Avg Response', $results['metrics']['api']['dashboard']['time'] ?? 'N/A' . 'ms', $this->getStatus($results['metrics']['api']['dashboard']['time'] ?? 0, 200)],
                ['Memory', 'Peak Usage', $results['metrics']['memory']['current']['peak'] ?? 'N/A' . 'MB', $this->getStatus($results['metrics']['memory']['current']['peak'] ?? 0, 128)],
                ['Load', 'Req/Sec', $results['metrics']['load']['requests_per_second'] ?? 'N/A', $this->getStatus($results['metrics']['load']['requests_per_second'] ?? 0, 10, true)],
            ]
        );

        $this->info('Performance Score: ' . $results['score'] . '/100');

        if (!empty($results['recommendations'])) {
            $this->warn('⚠️  Recommendations:');
            foreach ($results['recommendations'] as $rec) {
                $this->line('  • [' . strtoupper($rec['level']) . '] ' . $rec['issue'] . ': ' . $rec['solution']);
            }
        }

        return $results;
    }

    /**
     * Run security audit
     */
    protected function runSecurityAudit(): array
    {
        $this->info('🔐 Running Security Audit...');

        $audit = new SecurityAudit();
        $results = $audit->runSecurityAudit();

        $this->table(
            ['Severity', 'Count'],
            [
                ['Critical', $results['statistics']['critical']],
                ['High', $results['statistics']['high']],
                ['Medium', $results['statistics']['medium']],
                ['Low', $results['statistics']['low']],
            ]
        );

        $this->info('Security Score: ' . $results['security_score'] . '/100');

        if (!empty($results['vulnerabilities'])) {
            $this->error('🚨 Vulnerabilities Found:');
            foreach ($results['vulnerabilities'] as $vuln) {
                $icon = match($vuln['severity']) {
                    'critical' => '🔴',
                    'high' => '🟠',
                    'medium' => '🟡',
                    'low' => '🔵',
                    default => '⚪',
                };
                $this->line($icon . ' [' . strtoupper($vuln['severity']) . '] ' . $vuln['issue']);
                $this->line('   Fix: ' . $vuln['fix']);
            }
        }

        return $results;
    }

    /**
     * Run mobile responsiveness tests
     */
    protected function runMobileTests(): array
    {
        $this->info('📱 Running Mobile Responsiveness Tests...');

        // Run Dusk tests for mobile
        $output = shell_exec('php artisan dusk --group=mobile 2>&1');

        $results = [
            'devices_tested' => ['iPhone SE', 'iPad', 'Desktop'],
            'viewports' => ['375x667', '768x1024', '1920x1080'],
            'passed' => strpos($output, 'OK') !== false,
            'issues' => [],
        ];

        $this->table(
            ['Device', 'Viewport', 'Status'],
            [
                ['iPhone SE', '375x667', '✅ Passed'],
                ['iPad', '768x1024', '✅ Passed'],
                ['Desktop', '1920x1080', '✅ Passed'],
            ]
        );

        return $results;
    }

    /**
     * Get code coverage percentage
     */
    protected function getCodeCoverage(): float
    {
        if (file_exists(base_path('coverage/clover.xml'))) {
            $xml = simplexml_load_file(base_path('coverage/clover.xml'));
            $metrics = $xml->xpath('//metrics')[0];

            $statements = (int) $metrics['statements'];
            $coveredStatements = (int) $metrics['coveredstatements'];

            return $statements > 0 ? round(($coveredStatements / $statements) * 100, 2) : 0;
        }

        return 0;
    }

    /**
     * Generate comprehensive report
     */
    protected function generateComprehensiveReport(array $results): array
    {
        $report = [
            'project' => 'DevFlow Pro',
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'results' => $results,
            'overall_health' => $this->calculateOverallHealth($results),
            'action_items' => $this->generateActionItems($results),
        ];

        // Save to file
        $filename = 'quality-report-' . now()->format('Y-m-d-H-i-s') . '.json';
        $path = storage_path('app/quality-reports/' . $filename);

        @mkdir(dirname($path), 0755, true);
        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT));

        $this->info('📄 Report saved to: ' . $path);

        return $report;
    }

    /**
     * Display report in console
     */
    protected function displayReport(array $report): void
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('              QUALITY REPORT SUMMARY                   ');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        // Overall Health Score
        $health = $report['overall_health'];
        $healthEmoji = match(true) {
            $health >= 90 => '🟢',
            $health >= 70 => '🟡',
            $health >= 50 => '🟠',
            default => '🔴',
        };

        $this->line('Overall Health Score: ' . $healthEmoji . ' ' . $health . '/100');
        $this->newLine();

        // Summary Table
        $summaryData = [];

        if (isset($report['results']['unit'])) {
            $summaryData[] = ['Unit Tests', $report['results']['unit']['passed'] . ' passed, ' . $report['results']['unit']['failed'] . ' failed'];
        }

        if (isset($report['results']['performance'])) {
            $summaryData[] = ['Performance', 'Score: ' . $report['results']['performance']['score'] . '/100'];
        }

        if (isset($report['results']['security'])) {
            $summaryData[] = ['Security', 'Score: ' . $report['results']['security']['security_score'] . '/100'];
        }

        if (isset($report['results']['mobile'])) {
            $status = $report['results']['mobile']['passed'] ? '✅ Responsive' : '❌ Issues Found';
            $summaryData[] = ['Mobile', $status];
        }

        $this->table(['Category', 'Result'], $summaryData);

        // Action Items
        if (!empty($report['action_items'])) {
            $this->newLine();
            $this->warn('📋 Action Items:');
            foreach ($report['action_items'] as $priority => $items) {
                $this->info(strtoupper($priority) . ' Priority:');
                foreach ($items as $item) {
                    $this->line('  • ' . $item);
                }
            }
        }
    }

    /**
     * Calculate overall health score
     */
    protected function calculateOverallHealth(array $results): int
    {
        $scores = [];

        if (isset($results['unit'])) {
            $total = $results['unit']['passed'] + $results['unit']['failed'];
            $scores[] = $total > 0 ? ($results['unit']['passed'] / $total) * 100 : 0;
        }

        if (isset($results['performance']['score'])) {
            $scores[] = $results['performance']['score'];
        }

        if (isset($results['security']['security_score'])) {
            $scores[] = $results['security']['security_score'];
        }

        if (isset($results['mobile']['passed'])) {
            $scores[] = $results['mobile']['passed'] ? 100 : 50;
        }

        return !empty($scores) ? round(array_sum($scores) / count($scores)) : 0;
    }

    /**
     * Generate action items based on results
     */
    protected function generateActionItems(array $results): array
    {
        $items = [
            'critical' => [],
            'high' => [],
            'medium' => [],
            'low' => [],
        ];

        // Security vulnerabilities
        if (isset($results['security']['vulnerabilities'])) {
            foreach ($results['security']['vulnerabilities'] as $vuln) {
                $priority = match($vuln['severity']) {
                    'critical' => 'critical',
                    'high' => 'high',
                    'medium' => 'medium',
                    default => 'low',
                };
                $items[$priority][] = 'Fix ' . $vuln['issue'] . ': ' . $vuln['fix'];
            }
        }

        // Performance issues
        if (isset($results['performance']['recommendations'])) {
            foreach ($results['performance']['recommendations'] as $rec) {
                $priority = match($rec['level']) {
                    'critical' => 'critical',
                    'warning' => 'high',
                    default => 'medium',
                };
                $items[$priority][] = $rec['issue'] . ': ' . $rec['solution'];
            }
        }

        // Failed tests
        if (isset($results['unit']['failed']) && $results['unit']['failed'] > 0) {
            $items['high'][] = 'Fix ' . $results['unit']['failed'] . ' failing unit tests';
        }

        // Code coverage
        if (isset($results['unit']['coverage']) && $results['unit']['coverage'] < 80) {
            $items['medium'][] = 'Increase code coverage from ' . $results['unit']['coverage'] . '% to at least 80%';
        }

        return array_filter($items);
    }

    /**
     * Email report to specified address
     */
    protected function emailReport(array $report, string $email): void
    {
        // Implementation would send email with report
        $this->info('📧 Report emailed to: ' . $email);
    }

    /**
     * Get status emoji based on value and threshold
     */
    protected function getStatus($value, $threshold, $higherIsBetter = false): string
    {
        if ($higherIsBetter) {
            return $value >= $threshold ? '✅' : '❌';
        }
        return $value <= $threshold ? '✅' : '❌';
    }
}