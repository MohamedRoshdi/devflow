<?php

declare(strict_types=1);

namespace App\Services\LogParsers;

/**
 * Parser for MySQL error logs.
 */
final class MysqlLogParser extends AbstractLogParser
{
    /**
     * Regex for MySQL error log format.
     * Example: 2025-11-28T10:30:45.123456Z 123 [ERROR] message
     */
    private const LOG_PATTERN = '/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d+Z) \d+ \[(.*?)\] (.*)$/';

    public function getSource(): string
    {
        return 'mysql';
    }

    public function canParseLine(string $line): bool
    {
        return preg_match(self::LOG_PATTERN, $line) === 1;
    }

    protected function parseLines(array $lines): array
    {
        $logs = [];

        foreach ($lines as $line) {
            if (preg_match(self::LOG_PATTERN, $line, $matches) === 1) {
                $logs[] = $this->buildEntry(
                    level: $matches[2],
                    message: $matches[3],
                    loggedAt: $this->parseDate($matches[1])
                );
            }
        }

        return $logs;
    }
}
