<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Projects\InstallScriptGenerator;
use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Livewire\Livewire;
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
        ]);
    }

    #[Test]
    public function it_renders_the_component(): void
    {
        Livewire::actingAs($this->user)
            ->test(InstallScriptGenerator::class, ['project' => $this->project])
            ->assertStatus(200)
            ->assertSee(__('install_script.generate_script'));
    }

    #[Test]
    public function it_opens_and_closes_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(InstallScriptGenerator::class, ['project' => $this->project])
            ->assertSet('showModal', false)
            ->call('openModal')
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false);
    }

    #[Test]
    public function it_generates_development_script(): void
    {
        Livewire::actingAs($this->user)
            ->test(InstallScriptGenerator::class, ['project' => $this->project])
            ->set('productionMode', false)
            ->set('dbDriver', 'pgsql')
            ->call('generateScript')
            ->assertSet('showScript', true)
            ->assertSee('#!/bin/bash');
    }

    #[Test]
    public function it_requires_domain_for_production_mode(): void
    {
        Livewire::actingAs($this->user)
            ->test(InstallScriptGenerator::class, ['project' => $this->project])
            ->set('productionMode', true)
            ->set('domain', '')
            ->set('email', 'admin@example.com')
            ->call('generateScript')
            ->assertHasErrors(['domain']);
    }

    #[Test]
    public function it_requires_email_for_production_mode(): void
    {
        Livewire::actingAs($this->user)
            ->test(InstallScriptGenerator::class, ['project' => $this->project])
            ->set('productionMode', true)
            ->set('domain', 'test.example.com')
            ->set('email', '')
            ->call('generateScript')
            ->assertHasErrors(['email']);
    }

    #[Test]
    public function it_generates_production_script_with_valid_inputs(): void
    {
        Livewire::actingAs($this->user)
            ->test(InstallScriptGenerator::class, ['project' => $this->project])
            ->set('productionMode', true)
            ->set('domain', 'test.example.com')
            ->set('email', 'admin@example.com')
            ->set('dbDriver', 'pgsql')
            ->set('enableUfw', true)
            ->set('enableFail2ban', true)
            ->call('generateScript')
            ->assertSet('showScript', true);
    }

    #[Test]
    public function it_resets_script_on_reconfigure(): void
    {
        Livewire::actingAs($this->user)
            ->test(InstallScriptGenerator::class, ['project' => $this->project])
            ->set('productionMode', false)
            ->call('generateScript')
            ->assertSet('showScript', true)
            ->call('resetScript')
            ->assertSet('showScript', false)
            ->assertSet('generatedScript', '');
    }

    #[Test]
    public function it_calculates_script_line_count(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(InstallScriptGenerator::class, ['project' => $this->project])
            ->set('productionMode', false)
            ->call('generateScript');

        $this->assertGreaterThan(100, $component->get('scriptLineCount'));
    }

    #[Test]
    public function it_calculates_estimated_install_time(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(InstallScriptGenerator::class, ['project' => $this->project])
            ->set('productionMode', false)
            ->set('enableRedis', true)
            ->set('enableSupervisor', true);

        $estimatedTime = $component->get('estimatedInstallTime');
        $this->assertStringContainsString('minutes', $estimatedTime);
    }

    #[Test]
    public function it_uses_primary_domain_from_project(): void
    {
        Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'primary.example.com',
            'is_primary' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(InstallScriptGenerator::class, ['project' => $this->project])
            ->assertSet('domain', 'primary.example.com');
    }

    #[Test]
    public function it_dispatches_copy_event(): void
    {
        Livewire::actingAs($this->user)
            ->test(InstallScriptGenerator::class, ['project' => $this->project])
            ->call('generateScript')
            ->call('copyToClipboard')
            ->assertDispatched('copy-to-clipboard');
    }

    #[Test]
    public function it_validates_queue_workers_range(): void
    {
        Livewire::actingAs($this->user)
            ->test(InstallScriptGenerator::class, ['project' => $this->project])
            ->set('queueWorkers', 15)
            ->call('generateScript')
            ->assertHasErrors(['queueWorkers']);
    }

    #[Test]
    public function it_validates_db_driver_options(): void
    {
        Livewire::actingAs($this->user)
            ->test(InstallScriptGenerator::class, ['project' => $this->project])
            ->set('dbDriver', 'invalid')
            ->call('generateScript')
            ->assertHasErrors(['dbDriver']);
    }
}
