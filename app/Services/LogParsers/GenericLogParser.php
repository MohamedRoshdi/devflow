<?php

declare(strict_types=1);

namespace App\Services\LogParsers;

/**
 * Generic fallback parser for unknown log formats.
 */
final class GenericLogParser extends AbstractLogParser
{
    public function __construct(
        private readonly string $source = 'other'
    ) {}

    public function getSource(): string
    {
        return $this->source;
    }

    public function canParseLine(string $line): bool
    {
        // Generic parser accepts any non-empty line
        return trim($line) !== '';
    }

    protected function parseLines(array $lines): array
    {
        $logs = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                continue;
            }

            $logs[] = $this->buildEntry(
                level: 'info',
                message: $trimmed,
                loggedAt: now()
            );
        }

        return $logs;
    }
}
