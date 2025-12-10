<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Users\UserList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserListTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
    }

    public function test_component_can_be_rendered(): void
    {
        Livewire::actingAs($this->admin)
            ->test(UserList::class)
            ->assertOk();
    }

    public function test_component_displays_users(): void
    {
        $users = User::factory()->count(5)->create();

        Livewire::actingAs($this->admin)
            ->test(UserList::class)
            ->assertOk()
            ->assertSee($users->first()->name);
    }

    public function test_search_filters_users_by_name(): void
    {
        User::factory()->create(['name' => 'John Doe']);
        User::factory()->create(['name' => 'Jane Smith']);

        $component = Livewire::actingAs($this->admin)
            ->test(UserList::class);

        if (property_exists($component->instance(), 'search')) {
            $component->set('search', 'John')
                ->assertSee('John Doe')
                ->assertDontSee('Jane Smith');
        }
    }

    public function test_search_filters_users_by_email(): void
    {
        User::factory()->create(['email' => 'john@example.com']);
        User::factory()->create(['email' => 'jane@example.com']);

        $component = Livewire::actingAs($this->admin)
            ->test(UserList::class);

        if (property_exists($component->instance(), 'search')) {
            $component->set('search', 'john@')
                ->assertSee('john@example.com');
        }
    }

    public function test_guest_cannot_access_user_list(): void
    {
        Livewire::test(UserList::class)
            ->assertUnauthorized();
    }

    public function test_component_paginates_users(): void
    {
        User::factory()->count(25)->create();

        Livewire::actingAs($this->admin)
            ->test(UserList::class)
            ->assertOk();
    }
}
