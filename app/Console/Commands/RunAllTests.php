<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RunAllTests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tests:run-all
                            {--output-path=storage/test-results : Path to store test results}
                            {--parallel : Run tests in parallel}
                            {--filter= : Filter tests by name}
                            {--suite= : Run specific test suite (Unit, Feature, Browser, Performance, Security)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all tests and collect results to a specified path';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $outputPath = $this->option('output-path');
        $parallel = $this->option('parallel');
        $filter = $this->option('filter');
        $suite = $this->option('suite');

        // Ensure output directory exists
        $fullPath = base_path((string) $outputPath);
        if (! File::isDirectory($fullPath)) {
            File::makeDirectory($fullPath, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $this->info("Starting test run at {$timestamp}");
        $this->info("Results will be saved to: {$fullPath}");
        $this->newLine();

        $results = [];
        $suites = $suite ? [$suite] : ['Unit', 'Feature', 'Performance', 'Security'];

        foreach ($suites as $testSuite) {
            $this->info("Running {$testSuite} tests...");

            $result = $this->runTestSuite($testSuite, $fullPath, $timestamp, $parallel, $filter);
            $results[$testSuite] = $result;

            if ($result['passed']) {
                $this->info("✓ {$testSuite}: {$result['tests']} tests passed");
            } else {
                $this->error("✗ {$testSuite}: {$result['failures']} failures, {$result['errors']} errors");
            }
            $this->newLine();
        }

        // Generate summary report
        $this->generateSummaryReport($results, $fullPath, $timestamp);

        // Check overall status
        $allPassed = collect($results)->every(fn ($r) => $r['passed']);

        if ($allPassed) {
            $this->info('All tests passed!');
            return Command::SUCCESS;
        }

        $this->error('Some tests failed. Check the reports in: ' . $fullPath);
        return Command::FAILURE;
    }

    /**
     * Run a specific test suite.
     */
    private function runTestSuite(string $suite, string $outputPath, string $timestamp, bool $parallel, ?string $filter): array
    {
        $outputFile = "{$outputPath}/{$suite}_{$timestamp}.txt";
        $junitFile = "{$outputPath}/{$suite}_{$timestamp}.xml";

        $command = [
            'php',
            'artisan',
            'test',
            "--testsuite={$suite}",
            "--log-junit={$junitFile}",
        ];

        if ($parallel) {
            $command[] = '--parallel';
        }

        if ($filter) {
            $command[] = "--filter={$filter}";
        }

        $commandStr = implode(' ', $command) . " 2>&1 | tee {$outputFile}";

        $startTime = microtime(true);
        exec($commandStr, $output, $exitCode);
        $duration = round(microtime(true) - $startTime, 2);

        $outputText = implode("\n", $output);
        File::put($outputFile, $outputText);

        // Parse results
        $tests = 0;
        $failures = 0;
        $errors = 0;

        if (preg_match('/Tests:\s*(\d+)\s*passed/', $outputText, $matches)) {
            $tests = (int) $matches[1];
        }
        if (preg_match('/(\d+)\s*failed/', $outputText, $matches)) {
            $failures = (int) $matches[1];
        }
        if (preg_match('/(\d+)\s*errors?/', $outputText, $matches)) {
            $errors = (int) $matches[1];
        }

        // Extract failed test names
        $failedTests = [];
        if (preg_match_all('/FAILED\s+([^\s]+)/', $outputText, $matches)) {
            $failedTests = $matches[1];
        }

        return [
            'suite' => $suite,
            'passed' => $exitCode === 0,
            'exit_code' => $exitCode,
            'tests' => $tests,
            'failures' => $failures,
            'errors' => $errors,
            'duration' => $duration,
            'output_file' => $outputFile,
            'junit_file' => $junitFile,
            'failed_tests' => $failedTests,
            'output' => $outputText,
        ];
    }

    /**
     * Generate a summary report.
     */
    private function generateSummaryReport(array $results, string $outputPath, string $timestamp): void
    {
        $summaryFile = "{$outputPath}/summary_{$timestamp}.md";

        $totalTests = collect($results)->sum('tests');
        $totalFailures = collect($results)->sum('failures');
        $totalErrors = collect($results)->sum('errors');
        $totalDuration = collect($results)->sum('duration');
        $allPassed = collect($results)->every(fn ($r) => $r['passed']);

        $content = "# Test Results Summary\n\n";
        $content .= "**Date:** " . now()->format('Y-m-d H:i:s') . "\n";
        $content .= "**Status:** " . ($allPassed ? '✅ ALL PASSED' : '❌ FAILURES DETECTED') . "\n\n";

        $content .= "## Overview\n\n";
        $content .= "| Metric | Value |\n";
        $content .= "|--------|-------|\n";
        $content .= "| Total Tests | " . (string) $totalTests . " |\n";
        $content .= "| Failures | " . (string) $totalFailures . " |\n";
        $content .= "| Errors | " . (string) $totalErrors . " |\n";
        $content .= "| Duration | " . (string) $totalDuration . "s |\n\n";

        $content .= "## Suite Results\n\n";
        $content .= "| Suite | Status | Tests | Failures | Errors | Duration |\n";
        $content .= "|-------|--------|-------|----------|--------|----------|\n";

        foreach ($results as $suite => $result) {
            $status = $result['passed'] ? '✅' : '❌';
            $content .= "| {$suite} | {$status} | {$result['tests']} | {$result['failures']} | {$result['errors']} | {$result['duration']}s |\n";
        }

        // List failed tests
        $allFailedTests = collect($results)->flatMap(
            /** @return array<int, string> */
            fn (array $r): array => $r['failed_tests']
        )->filter()->values();
        if ($allFailedTests->isNotEmpty()) {
            $content .= "\n## Failed Tests\n\n";
            foreach ($allFailedTests as $test) {
                $content .= "- `{$test}`\n";
            }
        }

        // Add file references
        $content .= "\n## Result Files\n\n";
        foreach ($results as $suite => $result) {
            $content .= "- **{$suite}:** `{$result['output_file']}`\n";
        }

        File::put($summaryFile, $content);
        $this->info("Summary saved to: {$summaryFile}");
    }
}
