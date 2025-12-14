<?php

declare(strict_types=1);

namespace App\Services\LogParsers;

/**
 * Parser for Laravel/Monolog log format.
 *
 * Handles multi-line entries including stack traces.
 */
final class LaravelLogParser extends AbstractLogParser
{
    /**
     * Regex for Laravel log entry start.
     * Example: [2025-11-28 10:30:45] local.ERROR: Message
     */
    private const LOG_PATTERN = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.(\w+): (.*)$/';

    /**
     * Regex for stack trace file/line.
     * Example: #0 /path/to/file.php(123): function()
     */
    private const STACK_TRACE_PATTERN = '/^#\d+ (.+?)\((\d+)\)/';

    public function getSource(): string
    {
        return 'laravel';
    }

    public function canParseLine(string $line): bool
    {
        return preg_match(self::LOG_PATTERN, $line) === 1;
    }

    protected function parseLines(array $lines): array
    {
        $logs = [];
        $currentLog = null;

        foreach ($lines as $line) {
            if (preg_match(self::LOG_PATTERN, $line, $matches) === 1) {
                // Save previous log entry
                if ($currentLog !== null) {
                    $logs[] = $currentLog;
                }

                // Start new entry
                $currentLog = $this->buildEntry(
                    level: $matches[2],
                    message: $matches[3],
                    loggedAt: $this->parseDate($matches[1])
                );

                continue;
            }

            // Handle continuation lines (stack traces)
            if ($currentLog !== null && $this->isContinuationLine($line)) {
                $currentLog['message'] .= "\n" . trim($line);

                // Extract file path and line number from stack trace
                if (preg_match(self::STACK_TRACE_PATTERN, $line, $matches) === 1) {
                    // Only set if not already set (keep first occurrence)
                    if (!isset($currentLog['file_path'])) {
                        $currentLog['file_path'] = $matches[1];
                        $currentLog['line_number'] = (int) $matches[2];
                    }
                }
            }
        }

        // Don't forget the last entry
        if ($currentLog !== null) {
            $logs[] = $currentLog;
        }

        return $logs;
    }

    /**
     * Check if a line is a continuation of the previous entry.
     */
    private function isContinuationLine(string $line): bool
    {
        return str_starts_with($line, '#')
            || str_starts_with($line, ' ')
            || str_starts_with($line, "\t");
    }
}
