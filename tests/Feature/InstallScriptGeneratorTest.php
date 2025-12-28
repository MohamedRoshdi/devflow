<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Services\InstallScriptGenerator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InstallScriptGeneratorTest extends TestCase
{
    protected User $user;

    protected Server $server;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->server = Server::factory()->create(['status' => 'online']);
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Test Project',
            'slug' => 'test-project',
            'php_version' => '8.4',
            'framework' => 'laravel',
            'branch' => 'main',
            'repository_url' => 'https://github.com/test/project.git',
        ]);
    }

    #[Test]
    public function it_generates_development_script(): void
    {
        $generator = new InstallScriptGenerator();

        $script = $generator->generate($this->project, [
            'production' => false,
            'db_driver' => 'pgsql',
        ]);

        $this->assertStringContainsString('#!/bin/bash', $script);
        $this->assertStringContainsString('Test Project', $script);
        $this->assertStringContainsString('test-project', $script);
        $this->assertStringContainsString('PRODUCTION_MODE=false', $script);
        $this->assertStringContainsString('PHP 8.4', $script);
        $this->assertStringContainsString('PostgreSQL 16', $script);
        // Check that production security installation commands are NOT present
        $this->assertStringNotContainsString('sudo ufw default deny incoming', $script);
        $this->assertStringNotContainsString('sudo apt-get install -y -qq fail2ban', $script);
    }

    #[Test]
    public function it_generates_production_script_with_security(): void
    {
        $generator = new InstallScriptGenerator();

        $script = $generator->generate($this->project, [
            'production' => true,
            'domain' => 'test-project.example.com',
            'email' => 'admin@example.com',
            'db_driver' => 'pgsql',
            'enable_ufw' => true,
            'enable_fail2ban' => true,
        ]);

        $this->assertStringContainsString('PRODUCTION_MODE=true', $script);
        $this->assertStringContainsString('test-project.example.com', $script);
        $this->assertStringContainsString('admin@example.com', $script);
        $this->assertStringContainsString('UFW Firewall', $script);
        $this->assertStringContainsString('fail2ban', $script);
        $this->assertStringContainsString('certbot', $script);
        $this->assertStringContainsString('opcache.enable=1', $script);
        $this->assertStringContainsString('opcache.jit=1255', $script);
    }

    #[Test]
    public function it_generates_mysql_script(): void
    {
        $generator = new InstallScriptGenerator();

        $script = $generator->generate($this->project, [
            'production' => false,
            'db_driver' => 'mysql',
        ]);

        $this->assertStringContainsString('MySQL 8', $script);
        $this->assertStringContainsString('mysql-server', $script);
        $this->assertStringNotContainsString('PostgreSQL', $script);
    }

    #[Test]
    public function it_generates_script_with_redis(): void
    {
        $generator = new InstallScriptGenerator();

        $script = $generator->generate($this->project, [
            'enable_redis' => true,
        ]);

        $this->assertStringContainsString('Redis Installation', $script);
        $this->assertStringContainsString('redis-server', $script);
    }

    #[Test]
    public function it_generates_script_without_redis(): void
    {
        $generator = new InstallScriptGenerator();

        $script = $generator->generate($this->project, [
            'enable_redis' => false,
        ]);

        $this->assertStringNotContainsString('Redis Installation', $script);
    }

    #[Test]
    public function it_generates_script_with_supervisor(): void
    {
        $generator = new InstallScriptGenerator();

        $script = $generator->generate($this->project, [
            'enable_supervisor' => true,
            'queue_workers' => 3,
        ]);

        $this->assertStringContainsString('Supervisor Installation', $script);
        $this->assertStringContainsString('test-project-worker', $script);
        $this->assertStringContainsString('numprocs=3', $script);
    }

    #[Test]
    public function it_generates_script_with_ssl_skip(): void
    {
        $generator = new InstallScriptGenerator();

        $script = $generator->generate($this->project, [
            'production' => true,
            'domain' => 'test.example.com',
            'email' => 'admin@example.com',
            'skip_ssl' => true,
        ]);

        $this->assertStringContainsString('SKIP_SSL', $script);
    }

    #[Test]
    public function it_generates_generic_script(): void
    {
        $generator = new InstallScriptGenerator();

        $script = $generator->generateGeneric([
            'project_name' => 'Custom App',
            'project_slug' => 'custom-app',
            'db_driver' => 'pgsql',
            'php_version' => '8.3',
        ]);

        $this->assertStringContainsString('Custom App', $script);
        $this->assertStringContainsString('custom-app', $script);
        $this->assertStringContainsString('PHP 8.3', $script);
    }

    #[Test]
    public function it_uses_primary_domain_from_project(): void
    {
        Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'primary.example.com',
            'is_primary' => true,
        ]);

        $generator = new InstallScriptGenerator();

        $script = $generator->generate($this->project);

        $this->assertStringContainsString('primary.example.com', $script);
    }

    #[Test]
    public function it_includes_nginx_configuration(): void
    {
        $generator = new InstallScriptGenerator();

        $script = $generator->generate($this->project);

        $this->assertStringContainsString('Nginx Installation', $script);
        $this->assertStringContainsString('sites-available', $script);
        $this->assertStringContainsString('fastcgi_pass', $script);
        $this->assertStringContainsString('X-Frame-Options', $script);
    }

    #[Test]
    public function it_includes_cron_setup(): void
    {
        $generator = new InstallScriptGenerator();

        $script = $generator->generate($this->project);

        $this->assertStringContainsString('Cron Job Setup', $script);
        $this->assertStringContainsString('schedule:run', $script);
        $this->assertStringContainsString('crontab', $script);
    }

    #[Test]
    public function it_includes_laravel_commands(): void
    {
        $generator = new InstallScriptGenerator();

        $script = $generator->generate($this->project);

        $this->assertStringContainsString('php artisan key:generate', $script);
        $this->assertStringContainsString('php artisan migrate', $script);
        $this->assertStringContainsString('php artisan storage:link', $script);
        $this->assertStringContainsString('composer install', $script);
        $this->assertStringContainsString('npm install', $script);
        $this->assertStringContainsString('npm run build', $script);
    }

    #[Test]
    public function it_generates_help_function(): void
    {
        $generator = new InstallScriptGenerator();

        $script = $generator->generate($this->project);

        $this->assertStringContainsString('show_help()', $script);
        $this->assertStringContainsString('--production', $script);
        $this->assertStringContainsString('--domain', $script);
        $this->assertStringContainsString('--email', $script);
        $this->assertStringContainsString('--db-driver', $script);
    }

    #[Test]
    public function it_validates_production_requirements(): void
    {
        $generator = new InstallScriptGenerator();

        $script = $generator->generate($this->project, [
            'production' => true,
            'domain' => 'test.example.com',
            'email' => 'admin@example.com',
        ]);

        $this->assertStringContainsString('Domain is required for production mode', $script);
        $this->assertStringContainsString('Email is required for production mode', $script);
    }
}
