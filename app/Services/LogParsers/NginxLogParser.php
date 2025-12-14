<?php

declare(strict_types=1);

namespace App\Services\LogParsers;

/**
 * Parser for Nginx error and access logs.
 */
final class NginxLogParser extends AbstractLogParser
{
    /**
     * Regex for Nginx error log format.
     * Example: 2025/11/28 10:30:45 [error] 123#123: *456 message
     */
    private const ERROR_LOG_PATTERN = '/^(\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2}) \[(.*?)\].*?: (.*)$/';

    /**
     * Regex for Nginx access log format.
     * Example: 192.168.1.1 - - [28/Nov/2025:10:30:45 +0000] "GET /..."
     */
    private const ACCESS_LOG_PATTERN = '/^\S+ - - \[(.*?)\]/';

    public function getSource(): string
    {
        return 'nginx';
    }

    public function canParseLine(string $line): bool
    {
        return preg_match(self::ERROR_LOG_PATTERN, $line) === 1
            || preg_match(self::ACCESS_LOG_PATTERN, $line) === 1;
    }

    protected function parseLines(array $lines): array
    {
        $logs = [];

        foreach ($lines as $line) {
            $entry = $this->parseErrorLog($line) ?? $this->parseAccessLog($line);

            if ($entry !== null) {
                $logs[] = $entry;
            }
        }

        return $logs;
    }

    /**
     * @return array{source: string, level: string, message: string, logged_at: \Carbon\Carbon}|null
     */
    private function parseErrorLog(string $line): ?array
    {
        if (preg_match(self::ERROR_LOG_PATTERN, $line, $matches) !== 1) {
            return null;
        }

        return $this->buildEntry(
            level: $matches[2],
            message: $matches[3],
            loggedAt: $this->parseDate($matches[1], 'Y/m/d H:i:s')
        );
    }

    /**
     * @return array{source: string, level: string, message: string, logged_at: \Carbon\Carbon}|null
     */
    private function parseAccessLog(string $line): ?array
    {
        if (preg_match(self::ACCESS_LOG_PATTERN, $line, $matches) !== 1) {
            return null;
        }

        return $this->buildEntry(
            level: 'info',
            message: $line,
            loggedAt: $this->parseDate($matches[1], 'd/M/Y:H:i:s O')
        );
    }
}
