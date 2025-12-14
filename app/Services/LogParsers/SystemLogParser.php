<?php

declare(strict_types=1);

namespace App\Services\LogParsers;

/**
 * Parser for syslog/system logs.
 */
final class SystemLogParser extends AbstractLogParser
{
    /**
     * Regex for syslog format.
     * Example: Nov 28 10:30:45 hostname service[pid]: message
     */
    private const LOG_PATTERN = '/^(\w{3} \d{1,2} \d{2}:\d{2}:\d{2}) (\S+) (.*?): (.*)$/';

    public function getSource(): string
    {
        return 'system';
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
                // Syslog doesn't include year, append current year
                $dateWithYear = $matches[1] . ' ' . now()->year;

                $logs[] = $this->buildEntry(
                    level: 'info',
                    message: $matches[4],
                    loggedAt: $this->parseDate($dateWithYear),
                    context: [
                        'hostname' => $matches[2],
                        'service' => $matches[3],
                    ]
                );
            }
        }

        return $logs;
    }
}
