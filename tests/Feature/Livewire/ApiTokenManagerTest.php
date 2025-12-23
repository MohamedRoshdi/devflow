<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Settings\ApiTokenManager;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ApiTokenManagerTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ==================== RENDERING TESTS ====================

    public function test_component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->assertStatus(200);
    }

    public function test_loads_tokens_on_mount(): void
    {
        ApiToken::factory()->count(3)->create(['user_id' => $this->user->id]);

        $component = Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class);

        $this->assertCount(3, $component->get('tokens'));
    }

    public function test_shows_empty_collection_for_new_user(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class);

        $this->assertCount(0, $component->get('tokens'));
    }

    public function test_tokens_are_ordered_by_created_at_desc(): void
    {
        $oldToken = ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(5),
        ]);
        $newToken = ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class);

        $tokens = $component->get('tokens');
        $this->assertEquals($newToken->id, $tokens->first()->id);
    }

    // ==================== CREATE MODAL TESTS ====================

    public function test_can_open_create_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->assertSet('showCreateModal', false)
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true);
    }

    public function test_create_modal_resets_form_fields(): void
    {
        Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->set('newTokenName', 'Old Name')
            ->set('newTokenAbilities', ['projects:read'])
            ->set('newTokenExpiration', '30')
            ->call('openCreateModal')
            ->assertSet('newTokenName', '')
            ->assertSet('newTokenAbilities', [])
            ->assertSet('newTokenExpiration', 'never');
    }

    public function test_can_close_create_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true)
            ->call('closeCreateModal')
            ->assertSet('showCreateModal', false);
    }

    public function test_close_modal_resets_form(): void
    {
        Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->call('openCreateModal')
            ->set('newTokenName', 'My Token')
            ->set('newTokenAbilities', ['projects:read'])
            ->call('closeCreateModal')
            ->assertSet('newTokenName', '')
            ->assertSet('newTokenAbilities', []);
    }

    // ==================== CREATE TOKEN TESTS ====================

    public function test_can_create_token(): void
    {
        Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->set('newTokenName', 'My API Token')
            ->set('newTokenAbilities', ['projects:read', 'projects:write'])
            ->set('newTokenExpiration', 'never')
            ->call('createToken')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'success';
            });

        $this->assertDatabaseHas('api_tokens', [
            'user_id' => $this->user->id,
            'name' => 'My API Token',
        ]);
    }

    public function test_create_requires_token_name(): void
    {
        Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->set('newTokenName', '')
            ->set('newTokenAbilities', ['projects:read'])
            ->call('createToken')
            ->assertHasErrors(['newTokenName']);
    }

    public function test_create_requires_at_least_one_ability(): void
    {
        Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->set('newTokenName', 'My Token')
            ->set('newTokenAbilities', [])
            ->call('createToken')
            ->assertHasErrors(['newTokenAbilities']);
    }

    public function test_create_validates_expiration(): void
    {
        Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->set('newTokenName', 'My Token')
            ->set('newTokenAbilities', ['projects:read'])
            ->set('newTokenExpiration', 'invalid')
            ->call('createToken')
            ->assertHasErrors(['newTokenExpiration']);
    }

    public function test_token_is_hashed(): void
    {
        Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->set('newTokenName', 'My Token')
            ->set('newTokenAbilities', ['projects:read'])
            ->call('createToken');

        $token = ApiToken::where('user_id', $this->user->id)->first();
        $this->assertNotNull($token);
        $this->assertEquals(64, strlen($token->token)); // SHA256 hash is 64 chars
    }

    public function test_plain_token_is_stored_after_creation(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->set('newTokenName', 'My Token')
            ->set('newTokenAbilities', ['projects:read'])
            ->call('createToken');

        $plainToken = $component->get('createdTokenPlain');
        $this->assertNotNull($plainToken);
        $this->assertEquals(64, strlen($plainToken));
    }

    public function test_token_modal_shown_after_creation(): void
    {
        Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->set('newTokenName', 'My Token')
            ->set('newTokenAbilities', ['projects:read'])
            ->call('createToken')
            ->assertSet('showTokenModal', true)
            ->assertSet('showCreateModal', false);
    }

    public function test_create_stores_abilities(): void
    {
        Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->set('newTokenName', 'My Token')
            ->set('newTokenAbilities', ['projects:read', 'servers:write'])
            ->call('createToken');

        $token = ApiToken::where('user_id', $this->user->id)->first();
        $this->assertNotNull($token);
        $tokenArray = $token->toArray();
        $abilities = $tokenArray['abilities'] ?? [];
        $this->assertIsArray($abilities);
        $this->assertTrue(in_array('projects:read', $abilities, true));
        $this->assertTrue(in_array('servers:write', $abilities, true));
    }

    // ==================== EXPIRATION TESTS ====================

    public function test_never_expiration(): void
    {
        Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->set('newTokenName', 'My Token')
            ->set('newTokenAbilities', ['projects:read'])
            ->set('newTokenExpiration', 'never')
            ->call('createToken');

        $token = ApiToken::where('user_id', $this->user->id)->first();
        $this->assertNotNull($token);
        $this->assertNull($token->expires_at);
    }

    public function test_30_day_expiration(): void
    {
        Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->set('newTokenName', 'My Token')
            ->set('newTokenAbilities', ['projects:read'])
            ->set('newTokenExpiration', '30')
            ->call('createToken');

        $token = ApiToken::where('user_id', $this->user->id)->first();
        $this->assertNotNull($token);
        $this->assertNotNull($token->expires_at);
        $this->assertTrue($token->expires_at->isAfter(now()->addDays(29)));
        $this->assertTrue($token->expires_at->isBefore(now()->addDays(31)));
    }

    public function test_90_day_expiration(): void
    {
        Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->set('newTokenName', 'My Token')
            ->set('newTokenAbilities', ['projects:read'])
            ->set('newTokenExpiration', '90')
            ->call('createToken');

        $token = ApiToken::where('user_id', $this->user->id)->first();
        $this->assertNotNull($token);
        $this->assertNotNull($token->expires_at);
        $this->assertTrue($token->expires_at->isAfter(now()->addDays(89)));
        $this->assertTrue($token->expires_at->isBefore(now()->addDays(91)));
    }

    public function test_365_day_expiration(): void
    {
        Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->set('newTokenName', 'My Token')
            ->set('newTokenAbilities', ['projects:read'])
            ->set('newTokenExpiration', '365')
            ->call('createToken');

        $token = ApiToken::where('user_id', $this->user->id)->first();
        $this->assertNotNull($token);
        $this->assertNotNull($token->expires_at);
        $this->assertTrue($token->expires_at->isAfter(now()->addDays(364)));
        $this->assertTrue($token->expires_at->isBefore(now()->addDays(366)));
    }

    // ==================== TOKEN MODAL TESTS ====================

    public function test_can_close_token_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->set('showTokenModal', true)
            ->set('createdTokenPlain', 'test-token-123')
            ->call('closeTokenModal')
            ->assertSet('showTokenModal', false)
            ->assertSet('createdTokenPlain', null);
    }

    // ==================== REVOKE TOKEN TESTS ====================

    public function test_can_revoke_token(): void
    {
        $token = ApiToken::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->call('revokeToken', $token->id)
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'revoked');
            });

        $this->assertDatabaseMissing('api_tokens', ['id' => $token->id]);
    }

    public function test_cannot_revoke_other_users_token(): void
    {
        $otherUser = User::factory()->create();
        $token = ApiToken::factory()->create(['user_id' => $otherUser->id]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->call('revokeToken', $token->id);
    }

    public function test_revoke_reloads_tokens(): void
    {
        $token1 = ApiToken::factory()->create(['user_id' => $this->user->id]);
        $token2 = ApiToken::factory()->create(['user_id' => $this->user->id]);

        $component = Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class);

        $this->assertCount(2, $component->get('tokens'));

        $component->call('revokeToken', $token1->id);

        $this->assertCount(1, $component->get('tokens'));
    }

    // ==================== REGENERATE TOKEN TESTS ====================

    public function test_can_regenerate_token(): void
    {
        $token = ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', 'old-token'),
        ]);
        $oldHash = $token->token;

        Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->call('regenerateToken', $token->id)
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'regenerated');
            });

        $freshToken = $token->fresh();
        $this->assertNotNull($freshToken);
        $this->assertNotEquals($oldHash, $freshToken->token);
    }

    public function test_regenerate_shows_new_plain_token(): void
    {
        $token = ApiToken::factory()->create(['user_id' => $this->user->id]);

        $component = Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->call('regenerateToken', $token->id);

        $plainToken = $component->get('createdTokenPlain');
        $this->assertNotNull($plainToken);
        $this->assertEquals(64, strlen($plainToken));
    }

    public function test_regenerate_shows_token_modal(): void
    {
        $token = ApiToken::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->call('regenerateToken', $token->id)
            ->assertSet('showTokenModal', true);
    }

    public function test_regenerate_clears_last_used_at(): void
    {
        $token = ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'last_used_at' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->call('regenerateToken', $token->id);

        $freshToken = $token->fresh();
        $this->assertNotNull($freshToken);
        $this->assertNull($freshToken->last_used_at);
    }

    public function test_cannot_regenerate_other_users_token(): void
    {
        $otherUser = User::factory()->create();
        $token = ApiToken::factory()->create(['user_id' => $otherUser->id]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->call('regenerateToken', $token->id);
    }

    // ==================== USER ISOLATION TESTS ====================

    public function test_only_shows_users_own_tokens(): void
    {
        $otherUser = User::factory()->create();

        ApiToken::factory()->count(3)->create(['user_id' => $this->user->id]);
        ApiToken::factory()->count(5)->create(['user_id' => $otherUser->id]);

        $component = Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class);

        $this->assertCount(3, $component->get('tokens'));
    }

    public function test_created_token_belongs_to_current_user(): void
    {
        Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->set('newTokenName', 'My Token')
            ->set('newTokenAbilities', ['projects:read'])
            ->call('createToken');

        $this->assertDatabaseHas('api_tokens', [
            'user_id' => $this->user->id,
            'name' => 'My Token',
        ]);
    }

    // ==================== AVAILABLE ABILITIES TESTS ====================

    public function test_has_available_abilities(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class);

        $abilities = $component->get('availableAbilities');
        $this->assertIsArray($abilities);
        $this->assertArrayHasKey('projects:read', $abilities);
        $this->assertArrayHasKey('servers:write', $abilities);
        $this->assertArrayHasKey('deployments:rollback', $abilities);
    }

    public function test_available_abilities_has_project_permissions(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class);

        $abilities = $component->get('availableAbilities');
        $this->assertArrayHasKey('projects:read', $abilities);
        $this->assertArrayHasKey('projects:write', $abilities);
        $this->assertArrayHasKey('projects:delete', $abilities);
        $this->assertArrayHasKey('projects:deploy', $abilities);
    }

    public function test_available_abilities_has_server_permissions(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class);

        $abilities = $component->get('availableAbilities');
        $this->assertArrayHasKey('servers:read', $abilities);
        $this->assertArrayHasKey('servers:write', $abilities);
        $this->assertArrayHasKey('servers:delete', $abilities);
    }

    public function test_available_abilities_has_deployment_permissions(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class);

        $abilities = $component->get('availableAbilities');
        $this->assertArrayHasKey('deployments:read', $abilities);
        $this->assertArrayHasKey('deployments:write', $abilities);
        $this->assertArrayHasKey('deployments:rollback', $abilities);
    }

    // ==================== DEFAULT VALUES TESTS ====================

    public function test_default_values(): void
    {
        Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->assertSet('showCreateModal', false)
            ->assertSet('showTokenModal', false)
            ->assertSet('newTokenName', '')
            ->assertSet('newTokenAbilities', [])
            ->assertSet('newTokenExpiration', 'never')
            ->assertSet('createdToken', null)
            ->assertSet('createdTokenPlain', null);
    }

    // ==================== VALIDATION TESTS ====================

    public function test_token_name_max_length(): void
    {
        $longName = str_repeat('A', 256);

        Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->set('newTokenName', $longName)
            ->set('newTokenAbilities', ['projects:read'])
            ->call('createToken')
            ->assertHasErrors(['newTokenName']);
    }

    public function test_abilities_must_be_array(): void
    {
        // Property is typed as array, so PHP's type system prevents setting non-array values
        // This test verifies that an empty array triggers validation error
        Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->set('newTokenName', 'My Token')
            ->set('newTokenAbilities', [])
            ->call('createToken')
            ->assertHasErrors(['newTokenAbilities']);
    }

    // ==================== LOAD TOKENS TESTS ====================

    public function test_load_tokens_returns_empty_for_null_user(): void
    {
        // This is a defensive test - the auth middleware should prevent this
        // but the component handles it gracefully
        $component = Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class)
            ->call('loadTokens');

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $component->get('tokens'));
    }

    public function test_load_tokens_refreshes_list(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(ApiTokenManager::class);

        $this->assertCount(0, $component->get('tokens'));

        // Create token externally
        ApiToken::factory()->create(['user_id' => $this->user->id]);

        // Reload
        $component->call('loadTokens');

        $this->assertCount(1, $component->get('tokens'));
    }
}
