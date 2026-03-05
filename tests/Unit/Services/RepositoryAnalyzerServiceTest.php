<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\RepositoryAnalysis;
use App\Services\RepositoryAnalyzerService;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RepositoryAnalyzerServiceTest extends TestCase
{
    private RepositoryAnalyzerService $service;

    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RepositoryAnalyzerService();
        $this->tmpDir = sys_get_temp_dir().'/devflow_test_repo_'.uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmpDir)) {
            Process::run('rm -rf '.escapeshellarg($this->tmpDir));
        }
        parent::tearDown();
    }

    #[Test]
    public function it_detects_laravel_from_composer_json(): void
    {
        file_put_contents($this->tmpDir.'/composer.json', json_encode([
            'require' => [
                'php' => '^8.3',
                'laravel/framework' => '^11.0',
            ],
        ]));
        file_put_contents($this->tmpDir.'/package.json', json_encode([
            'scripts' => ['build' => 'vite build'],
        ]));

        $analysis = $this->analyzeDirectory();

        $this->assertSame('laravel', $analysis->framework);
        $this->assertSame('8.3', $analysis->phpVersion);
        $this->assertSame('npm run build', $analysis->buildCommand);
        $this->assertContains('composer.json', $analysis->detectedFiles);
        $this->assertArrayHasKey('framework', $analysis->sources);
    }

    #[Test]
    public function it_detects_nextjs_from_package_json(): void
    {
        file_put_contents($this->tmpDir.'/package.json', json_encode([
            'dependencies' => ['next' => '^14.0', 'react' => '^18.0'],
            'scripts' => ['build' => 'next build', 'start' => 'next start'],
            'engines' => ['node' => '>=20'],
        ]));

        $analysis = $this->analyzeDirectory();

        $this->assertSame('nextjs', $analysis->framework);
        $this->assertSame('20', $analysis->nodeVersion);
        $this->assertSame('npm run build', $analysis->buildCommand);
        $this->assertSame('npm start', $analysis->startCommand);
    }

    #[Test]
    public function it_detects_react_from_package_json(): void
    {
        file_put_contents($this->tmpDir.'/package.json', json_encode([
            'dependencies' => ['react' => '^18.0', 'react-dom' => '^18.0'],
            'scripts' => ['build' => 'react-scripts build'],
        ]));

        $analysis = $this->analyzeDirectory();

        $this->assertSame('react', $analysis->framework);
    }

    #[Test]
    public function it_detects_vue_from_package_json(): void
    {
        file_put_contents($this->tmpDir.'/package.json', json_encode([
            'dependencies' => ['vue' => '^3.0'],
            'scripts' => ['build' => 'vite build'],
        ]));

        $analysis = $this->analyzeDirectory();

        $this->assertSame('vue', $analysis->framework);
    }

    #[Test]
    public function it_detects_nuxt_from_package_json(): void
    {
        file_put_contents($this->tmpDir.'/package.json', json_encode([
            'dependencies' => ['nuxt' => '^3.0', 'vue' => '^3.0'],
            'scripts' => ['build' => 'nuxt build', 'start' => 'nuxt start'],
        ]));

        $analysis = $this->analyzeDirectory();

        // Nuxt takes priority over Vue
        $this->assertSame('nuxt', $analysis->framework);
    }

    #[Test]
    public function it_detects_docker_files(): void
    {
        file_put_contents($this->tmpDir.'/Dockerfile', "FROM php:8.4-fpm\nRUN apt-get update\n");
        file_put_contents($this->tmpDir.'/docker-compose.yml', "version: '3'\nservices:\n  app:\n    build: .\n");
        file_put_contents($this->tmpDir.'/composer.json', json_encode([
            'require' => ['php' => '^8.4', 'laravel/framework' => '^11.0'],
        ]));

        $analysis = $this->analyzeDirectory();

        $this->assertTrue($analysis->hasDockerfile);
        $this->assertTrue($analysis->hasDockerCompose);
        $this->assertSame('8.4', $analysis->phpVersion);
        $this->assertSame('docker', $analysis->suggestedDeploymentMethod);
    }

    #[Test]
    public function it_reads_node_version_from_nvmrc(): void
    {
        file_put_contents($this->tmpDir.'/package.json', json_encode([
            'dependencies' => ['react' => '^18.0'],
            'engines' => ['node' => '>=18'],
        ]));
        file_put_contents($this->tmpDir.'/.nvmrc', 'v22.11.0');

        $analysis = $this->analyzeDirectory();

        // .nvmrc overrides engines.node
        $this->assertSame('22', $analysis->nodeVersion);
        $this->assertSame('.nvmrc', $analysis->sources['nodeVersion']);
    }

    #[Test]
    public function it_reads_php_version_from_dockerfile_when_no_composer(): void
    {
        file_put_contents($this->tmpDir.'/Dockerfile', "FROM php:8.2-apache\nCOPY . /var/www/html\n");

        $analysis = $this->analyzeDirectory();

        $this->assertSame('8.2', $analysis->phpVersion);
        $this->assertSame('Dockerfile (FROM php)', $analysis->sources['phpVersion']);
    }

    #[Test]
    public function it_detects_node_version_from_dockerfile(): void
    {
        file_put_contents($this->tmpDir.'/Dockerfile', "FROM node:20-alpine\nWORKDIR /app\n");
        file_put_contents($this->tmpDir.'/package.json', json_encode([
            'dependencies' => ['express' => '^4.0'],
            'scripts' => ['start' => 'node server.js'],
        ]));

        $analysis = $this->analyzeDirectory();

        $this->assertSame('20', $analysis->nodeVersion);
        $this->assertSame('nodejs', $analysis->framework);
    }

    #[Test]
    public function it_suggests_bare_metal_for_laravel_without_docker(): void
    {
        file_put_contents($this->tmpDir.'/composer.json', json_encode([
            'require' => ['php' => '^8.3', 'laravel/framework' => '^11.0'],
        ]));

        $analysis = $this->analyzeDirectory();

        $this->assertSame('standard', $analysis->suggestedDeploymentMethod);
    }

    #[Test]
    public function it_suggests_docker_for_node_frameworks(): void
    {
        file_put_contents($this->tmpDir.'/package.json', json_encode([
            'dependencies' => ['next' => '^14.0', 'react' => '^18.0'],
            'scripts' => ['build' => 'next build', 'start' => 'next start'],
        ]));

        $analysis = $this->analyzeDirectory();

        $this->assertSame('docker', $analysis->suggestedDeploymentMethod);
    }

    #[Test]
    public function it_detects_static_site_from_index_html(): void
    {
        file_put_contents($this->tmpDir.'/index.html', '<html><body>Hello</body></html>');

        $analysis = $this->analyzeDirectory();

        $this->assertSame('static', $analysis->framework);
    }

    #[Test]
    public function it_prioritizes_laravel_over_node_frameworks(): void
    {
        // Laravel project with React frontend (common setup)
        file_put_contents($this->tmpDir.'/composer.json', json_encode([
            'require' => ['php' => '^8.4', 'laravel/framework' => '^11.0'],
        ]));
        file_put_contents($this->tmpDir.'/package.json', json_encode([
            'dependencies' => ['react' => '^18.0', 'react-dom' => '^18.0'],
            'scripts' => ['build' => 'vite build'],
        ]));

        $analysis = $this->analyzeDirectory();

        $this->assertSame('laravel', $analysis->framework);
        $this->assertSame('8.4', $analysis->phpVersion);
    }

    #[Test]
    public function it_returns_high_confidence_for_complete_detection(): void
    {
        file_put_contents($this->tmpDir.'/composer.json', json_encode([
            'require' => ['php' => '^8.4', 'laravel/framework' => '^11.0'],
        ]));
        file_put_contents($this->tmpDir.'/package.json', json_encode([
            'scripts' => ['build' => 'vite build'],
        ]));
        file_put_contents($this->tmpDir.'/Dockerfile', "FROM php:8.4-fpm\n");

        $analysis = $this->analyzeDirectory();

        $this->assertSame('high', $analysis->confidence);
    }

    #[Test]
    public function it_returns_low_confidence_for_empty_repo(): void
    {
        // Empty directory - nothing to detect
        $analysis = $this->analyzeDirectory();

        $this->assertSame('low', $analysis->confidence);
        $this->assertNull($analysis->framework);
    }

    #[Test]
    public function repository_analysis_dto_serialization_roundtrip(): void
    {
        $original = new RepositoryAnalysis(
            framework: 'laravel',
            phpVersion: '8.4',
            nodeVersion: '20',
            hasDockerfile: true,
            hasDockerCompose: false,
            suggestedDeploymentMethod: 'docker',
            buildCommand: 'npm run build',
            startCommand: null,
            installCommands: ['composer install --no-dev', 'npm ci'],
            buildCommands: ['npm run build'],
            postDeployCommands: ['php artisan migrate --force'],
            detectedFiles: ['composer.json', 'package.json', 'Dockerfile'],
            confidence: 'high',
            sources: ['framework' => 'composer.json', 'phpVersion' => 'composer.json'],
        );

        $array = $original->toArray();
        $restored = RepositoryAnalysis::fromArray($array);

        $this->assertSame($original->framework, $restored->framework);
        $this->assertSame($original->phpVersion, $restored->phpVersion);
        $this->assertSame($original->nodeVersion, $restored->nodeVersion);
        $this->assertSame($original->hasDockerfile, $restored->hasDockerfile);
        $this->assertSame($original->hasDockerCompose, $restored->hasDockerCompose);
        $this->assertSame($original->suggestedDeploymentMethod, $restored->suggestedDeploymentMethod);
        $this->assertSame($original->buildCommand, $restored->buildCommand);
        $this->assertSame($original->startCommand, $restored->startCommand);
        $this->assertSame($original->installCommands, $restored->installCommands);
        $this->assertSame($original->buildCommands, $restored->buildCommands);
        $this->assertSame($original->postDeployCommands, $restored->postDeployCommands);
        $this->assertSame($original->detectedFiles, $restored->detectedFiles);
        $this->assertSame($original->confidence, $restored->confidence);
        $this->assertSame($original->sources, $restored->sources);
    }

    #[Test]
    public function it_handles_clone_failure_gracefully(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not clone repository');

        $this->service->analyze('https://github.com/nonexistent/repo-that-does-not-exist-12345.git', 'main');
    }

    /**
     * Helper to analyze a pre-populated temp directory without git clone.
     * Uses reflection to test the private scanRepository method directly.
     */
    private function analyzeDirectory(): RepositoryAnalysis
    {
        $reflection = new \ReflectionMethod($this->service, 'scanRepository');

        return $reflection->invoke($this->service, $this->tmpDir);
    }
}
