<?php

declare(strict_types=1);

namespace App\Services\LogParsers;

/**
 * Parser for PHP error logs.
 */
final class PhpLogParser extends AbstractLogParser
{
    /**
     * Regex for PHP error with file/line info.
     * Example: [28-Nov-2025 10:30:45 UTC] PHP Warning: message in /path/file.php on line 123
     */
    private const FULL_ERROR_PATTERN = '/^\[(\d{2}-\w{3}-\d{4} \d{2}:\d{2}:\d{2}.*?)\] PHP (.*?): (.*?) in (.*?) on line (\d+)$/';

    /**
     * Regex for generic PHP log entry.
     * Example: [28-Nov-2025 10:30:45 UTC] message
     */
    private const GENERIC_PATTERN = '/^\[(\d{2}-\w{3}-\d{4} \d{2}:\d{2}:\d{2}.*?)\] (.*)$/';

    public function getSource(): string
    {
        return 'php';
    }

    public function canParseLine(string $line): bool
    {
        return preg_match(self::FULL_ERROR_PATTERN, $line) === 1
            || preg_match(self::GENERIC_PATTERN, $line) === 1;
    }

    protected function parseLines(array $lines): array
    {
        $logs = [];

        foreach ($lines as $line) {
            $entry = $this->parseFullError($line) ?? $this->parseGenericEntry($line);

            if ($entry !== null) {
                $logs[] = $entry;
            }
        }

        return $logs;
    }

    /**
     * @return array{source: string, level: string, message: string, logged_at: \Carbon\Carbon, file_path: string, line_number: int}|null
     */
    private function parseFullError(string $line): ?array
    {
        if (preg_match(self::FULL_ERROR_PATTERN, $line, $matches) !== 1) {
            return null;
        }

        return $this->buildEntry(
            level: $matches[2],
            message: $matches[3],
            loggedAt: $this->parseDate($matches[1]),
            filePath: $matches[4],
            lineNumber: (int) $matches[5]
        );
    }

    /**
     * @return array{source: string, level: string, message: string, logged_at: \Carbon\Carbon}|null
     */
    private function parseGenericEntry(string $line): ?array
    {
        if (preg_match(self::GENERIC_PATTERN, $line, $matches) !== 1) {
            return null;
        }

        return $this->buildEntry(
            level: 'error',
            message: $matches[2],
            loggedAt: $this->parseDate($matches[1])
        );
    }
}
