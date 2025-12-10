<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Scripts\ScriptManager;
use App\Models\DeploymentScript;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScriptManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_component_can_be_rendered(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScriptManager::class)
            ->assertOk();
    }

    public function test_component_displays_deployment_scripts(): void
    {
        $scripts = DeploymentScript::factory()->count(5)->create();

        Livewire::actingAs($this->user)
            ->test(ScriptManager::class)
            ->assertOk()
            ->assertSee($scripts->first()->name);
    }

    public function test_guest_cannot_access_script_manager(): void
    {
        Livewire::test(ScriptManager::class)
            ->assertUnauthorized();
    }
}
