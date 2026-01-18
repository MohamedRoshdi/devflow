<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Interface for log parsers.
 *
 * Each parser implementation handles a specific log format
 * (e.g., Nginx, Laravel, PHP, MySQL, etc.).
 */
interface LogParserInterface
{
    /**
     * Get the source identifier for this parser.
     */
    public function getSource(): string;

    /**
     * Parse log content into structured entries.
     *
     * @param  string  $content  Raw log content
     * @return array<int, array{source: string, level: string, message: string, logged_at: \Carbon\Carbon, file_path?: string, line_number?: int, context?: array<string, mixed>}>
     */
    public function parse(string $content): array;

    /**
     * Check if a line matches this parser's format.
     */
    public function canParseLine(string $line): bool;
}
