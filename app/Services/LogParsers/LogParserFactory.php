<?php

declare(strict_types=1);

namespace App\Services\LogParsers;

use App\Contracts\LogParserInterface;

/**
 * Factory for creating log parsers.
 *
 * Uses the strategy pattern to select the appropriate parser
 * based on the log source type.
 */
final class LogParserFactory
{
    /**
     * Cached parser instances.
     *
     * @var array<string, LogParserInterface>
     */
    private array $parsers = [];

    /**
     * Get a parser for the specified source.
     */
    public function getParser(string $source): LogParserInterface
    {
        $source = strtolower($source);

        if (!isset($this->parsers[$source])) {
            $this->parsers[$source] = $this->createParser($source);
        }

        return $this->parsers[$source];
    }

    /**
     * Parse log content using the appropriate parser.
     *
     * @return array<int, array{source: string, level: string, message: string, logged_at: \Carbon\Carbon, file_path?: string, line_number?: int, context?: array<string, mixed>}>
     */
    public function parse(string $source, string $content): array
    {
        return $this->getParser($source)->parse($content);
    }

    /**
     * Get all available parser types.
     *
     * @return array<int, string>
     */
    public function getAvailableTypes(): array
    {
        return [
            'nginx',
            'laravel',
            'php',
            'mysql',
            'system',
            'docker',
        ];
    }

    /**
     * Create a new parser instance for the given source.
     */
    private function createParser(string $source): LogParserInterface
    {
        return match ($source) {
            'nginx' => new NginxLogParser(),
            'laravel' => new LaravelLogParser(),
            'php' => new PhpLogParser(),
            'mysql' => new MysqlLogParser(),
            'system' => new SystemLogParser(),
            'docker' => new DockerLogParser(),
            default => new GenericLogParser($source),
        };
    }
}
