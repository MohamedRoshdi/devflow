<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class RepositoryAnalysis
{
    /**
     * @param array<int, string> $installCommands
     * @param array<int, string> $buildCommands
     * @param array<int, string> $postDeployCommands
     * @param array<int, string> $detectedFiles
     * @param array<string, string> $sources
     */
    public function __construct(
        public ?string $framework = null,
        public ?string $phpVersion = null,
        public ?string $nodeVersion = null,
        public bool $hasDockerfile = false,
        public bool $hasDockerCompose = false,
        public ?string $suggestedDeploymentMethod = null,
        public ?string $buildCommand = null,
        public ?string $startCommand = null,
        public array $installCommands = [],
        public array $buildCommands = [],
        public array $postDeployCommands = [],
        public array $detectedFiles = [],
        public string $confidence = 'low',
        public array $sources = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'framework' => $this->framework,
            'phpVersion' => $this->phpVersion,
            'nodeVersion' => $this->nodeVersion,
            'hasDockerfile' => $this->hasDockerfile,
            'hasDockerCompose' => $this->hasDockerCompose,
            'suggestedDeploymentMethod' => $this->suggestedDeploymentMethod,
            'buildCommand' => $this->buildCommand,
            'startCommand' => $this->startCommand,
            'installCommands' => $this->installCommands,
            'buildCommands' => $this->buildCommands,
            'postDeployCommands' => $this->postDeployCommands,
            'detectedFiles' => $this->detectedFiles,
            'confidence' => $this->confidence,
            'sources' => $this->sources,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        /** @var array<int, string> $detectedFiles */
        $detectedFiles = array_values(array_map(
            static fn (mixed $v): string => (string) $v,
            (array) ($data['detectedFiles'] ?? [])
        ));

        /** @var array<string, string> $sources */
        $sources = array_map(
            static fn (mixed $v): string => (string) $v,
            array_filter(
                (array) ($data['sources'] ?? []),
                static fn (mixed $k): bool => is_string($k),
                ARRAY_FILTER_USE_KEY
            )
        );

        $toStringArray = static fn (mixed $arr): array => array_values(array_map(
            static fn (mixed $v): string => (string) $v,
            (array) ($arr ?? [])
        ));

        return new self(
            framework: isset($data['framework']) ? (string) $data['framework'] : null,
            phpVersion: isset($data['phpVersion']) ? (string) $data['phpVersion'] : null,
            nodeVersion: isset($data['nodeVersion']) ? (string) $data['nodeVersion'] : null,
            hasDockerfile: (bool) ($data['hasDockerfile'] ?? false),
            hasDockerCompose: (bool) ($data['hasDockerCompose'] ?? false),
            suggestedDeploymentMethod: isset($data['suggestedDeploymentMethod']) ? (string) $data['suggestedDeploymentMethod'] : null,
            buildCommand: isset($data['buildCommand']) ? (string) $data['buildCommand'] : null,
            startCommand: isset($data['startCommand']) ? (string) $data['startCommand'] : null,
            installCommands: $toStringArray($data['installCommands'] ?? []),
            buildCommands: $toStringArray($data['buildCommands'] ?? []),
            postDeployCommands: $toStringArray($data['postDeployCommands'] ?? []),
            detectedFiles: $detectedFiles,
            confidence: (string) ($data['confidence'] ?? 'low'),
            sources: $sources,
        );
    }
}
