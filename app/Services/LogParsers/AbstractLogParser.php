<?php

declare(strict_types=1);

namespace App\Services\LogParsers;

use App\Contracts\LogParserInterface;
use Carbon\Carbon;

/**
 * Abstract base class for log parsers.
 *
 * Provides common functionality for parsing logs including:
 * - Level normalization
 * - Line splitting
 * - Entry building
 */
abstract class AbstractLogParser implements LogParserInterface
{
    /**
     * Map of log level aliases to normalized values.
     *
     * @var array<string, string>
     */
    protected const LEVEL_MAP = [
        'warn' => 'warning',
        'warning' => 'warning',
        'err' => 'error',
        'error' => 'error',
        'crit' => 'critical',
        'critical' => 'critical',
        'emerg' => 'emergency',
        'emergency' => 'emergency',
        'notice' => 'notice',
        'debug' => 'debug',
        'alert' => 'alert',
        'info' => 'info',
    ];

    /**
     * {@inheritDoc}
     */
    public function parse(string $content): array
    {
        $lines = $this->splitLines($content);

        return $this->parseLines($lines);
    }

    /**
     * Parse an array of log lines.
     *
     * @param  array<int, string>  $lines
     * @return array<int, array{source: string, level: string, message: string, logged_at: Carbon, file_path?: string, line_number?: int, context?: array<string, mixed>}>
     */
    abstract protected function parseLines(array $lines): array;

    /**
     * Split content into lines, filtering empty ones.
     *
     * @return array<int, string>
     */
    protected function splitLines(string $content): array
    {
        return array_values(array_filter(
            explode("\n", $content),
            fn (string $line): bool => trim($line) !== ''
        ));
    }

    /**
     * Normalize a log level to standard values.
     */
    protected function normalizeLevel(string $level): string
    {
        $level = strtolower(trim($level));

        return self::LEVEL_MAP[$level] ?? 'info';
    }

    /**
     * Build a log entry array.
     *
     * @param  array<string, mixed>|null  $context
     * @return array{source: string, level: string, message: string, logged_at: Carbon, file_path?: string, line_number?: int, context?: array<string, mixed>}
     */
    protected function buildEntry(
        string $level,
        string $message,
        Carbon $loggedAt,
        ?string $filePath = null,
        ?int $lineNumber = null,
        ?array $context = null
    ): array {
        $entry = [
            'source' => $this->getSource(),
            'level' => $this->normalizeLevel($level),
            'message' => trim($message),
            'logged_at' => $loggedAt,
        ];

        if ($filePath !== null) {
            $entry['file_path'] = $filePath;
        }

        if ($lineNumber !== null) {
            $entry['line_number'] = $lineNumber;
        }

        if ($context !== null && $context !== []) {
            $entry['context'] = $context;
        }

        return $entry;
    }

    /**
     * Safely parse a date string, returning now() on failure.
     */
    protected function parseDate(string $dateString, ?string $format = null): Carbon
    {
        try {
            if ($format !== null) {
                return Carbon::createFromFormat($format, $dateString) ?? now();
            }

            return Carbon::parse($dateString);
        } catch (\Exception) {
            return now();
        }
    }
}
