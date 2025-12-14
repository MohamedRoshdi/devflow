<?php

declare(strict_types=1);

namespace App\Services\LogParsers;

/**
 * Parser for Docker container logs.
 */
final class DockerLogParser extends AbstractLogParser
{
    /**
     * Regex for Docker logs with ISO timestamp.
     * Example: 2025-11-28T10:30:45.123456Z message
     */
    private const TIMESTAMP_PATTERN = '/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d+Z) (.*)$/';

    public function getSource(): string
    {
        return 'docker';
    }

    public function canParseLine(string $line): bool
    {
        // Docker can output various formats, accept any non-empty line
        return trim($line) !== '';
    }

    protected function parseLines(array $lines): array
    {
        $logs = [];

        foreach ($lines as $line) {
            if (preg_match(self::TIMESTAMP_PATTERN, $line, $matches) === 1) {
                $logs[] = $this->buildEntry(
                    level: 'info',
                    message: $matches[2],
                    loggedAt: $this->parseDate($matches[1])
                );
            } else {
                // No timestamp found, use current time
                $logs[] = $this->buildEntry(
                    level: 'info',
                    message: $line,
                    loggedAt: now()
                );
            }
        }

        return $logs;
    }
}
