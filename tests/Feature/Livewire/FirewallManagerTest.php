<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Servers\Security\FirewallManager;
use App\Models\FirewallRule;
use App\Models\SecurityEvent;
use App\Models\Server;
use App\Models\User;
use App\Services\Security\FirewallService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class FirewallManagerTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $user;

    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->server = Server::factory()->create([
            'user_id' => $this->user->id,
            'ufw_installed' => true,
            'ufw_enabled' => false,
        ]);
    }

    public function test_component_renders_for_authenticated_users(): void
    {
        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->assertOk()
            ->assertSet('server.id', $this->server->id)
            ->assertSet('isLoading', false);
    }

    public function test_guest_cannot_access_component(): void
    {
        Livewire::test(FirewallManager::class, ['server' => $this->server])
            ->assertForbidden();
    }

    public function test_unauthorized_user_cannot_view_firewall_manager(): void
    {
        $otherUser = User::factory()->create();

        Livewire::actingAs($otherUser)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->assertForbidden();
    }

    public function test_component_loads_firewall_status_on_mount(): void
    {
        $this->mockFirewallService([
            'getUfwStatus' => [
                'installed' => true,
                'enabled' => true,
                'raw_output' => 'Status: active',
                'message' => 'UFW is active',
            ],
            'getRulesNumbered' => [
                'success' => true,
                'rules' => [
                    ['number' => 1, 'rule' => '22/tcp ALLOW Anywhere', 'parsed' => ['action' => 'allow']],
                    ['number' => 2, 'rule' => '80/tcp ALLOW Anywhere', 'parsed' => ['action' => 'allow']],
                ],
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->assertSet('ufwInstalled', true)
            ->assertSet('ufwEnabled', true)
            ->assertCount('rules', 2);
    }

    public function test_component_displays_not_installed_status(): void
    {
        $this->mockFirewallService([
            'getUfwStatus' => [
                'installed' => false,
                'enabled' => false,
                'rules' => [],
                'message' => 'UFW is not installed',
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->assertSet('ufwInstalled', false)
            ->assertSet('ufwEnabled', false)
            ->assertSee('UFW is not installed');
    }

    public function test_can_enable_firewall_successfully(): void
    {
        $this->mockFirewallService([
            'enableUfw' => [
                'success' => true,
                'message' => 'Firewall enabled successfully',
            ],
            'getUfwStatus' => [
                'installed' => true,
                'enabled' => true,
                'raw_output' => 'Status: active',
            ],
            'getRulesNumbered' => [
                'success' => true,
                'rules' => [],
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->call('enableFirewall')
            ->assertSet('flashMessage', 'Firewall enabled successfully')
            ->assertSet('flashType', 'success')
            ->assertSet('ufwEnabled', true);

        $this->server->refresh();
        $this->assertTrue($this->server->ufw_enabled);
    }

    public function test_enable_firewall_handles_failures(): void
    {
        $this->mockFirewallService([
            'enableUfw' => [
                'success' => false,
                'message' => 'Failed to enable firewall: Permission denied',
            ],
            'getUfwStatus' => [
                'installed' => true,
                'enabled' => false,
                'raw_output' => 'Status: inactive',
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->call('enableFirewall')
            ->assertSet('flashMessage', 'Failed to enable firewall: Permission denied')
            ->assertSet('flashType', 'error');
    }

    public function test_confirm_disable_firewall_opens_confirmation_modal(): void
    {
        $this->mockFirewallService();

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->assertSet('showConfirmDisable', false)
            ->call('confirmDisableFirewall')
            ->assertSet('showConfirmDisable', true);
    }

    public function test_can_disable_firewall_successfully(): void
    {
        $this->server->update(['ufw_enabled' => true]);

        $this->mockFirewallService([
            'disableUfw' => [
                'success' => true,
                'message' => 'Firewall disabled successfully',
            ],
            'getUfwStatus' => [
                'installed' => true,
                'enabled' => false,
                'raw_output' => 'Status: inactive',
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->set('showConfirmDisable', true)
            ->call('disableFirewall')
            ->assertSet('showConfirmDisable', false)
            ->assertSet('flashMessage', 'Firewall disabled successfully')
            ->assertSet('flashType', 'success')
            ->assertSet('ufwEnabled', false);

        $this->server->refresh();
        $this->assertFalse($this->server->ufw_enabled);
    }

    public function test_open_add_rule_modal_resets_form(): void
    {
        $this->mockFirewallService();

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->set('rulePort', '8080')
            ->set('ruleProtocol', 'udp')
            ->set('ruleAction', 'deny')
            ->call('openAddRuleModal')
            ->assertSet('showAddRuleModal', true)
            ->assertSet('rulePort', '')
            ->assertSet('ruleProtocol', 'tcp')
            ->assertSet('ruleAction', 'allow')
            ->assertSet('ruleFromIp', '')
            ->assertSet('ruleDescription', '');
    }

    public function test_close_add_rule_modal_resets_form(): void
    {
        $this->mockFirewallService();

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->set('showAddRuleModal', true)
            ->set('rulePort', '8080')
            ->call('closeAddRuleModal')
            ->assertSet('showAddRuleModal', false)
            ->assertSet('rulePort', '');
    }

    public function test_can_add_firewall_rule_successfully(): void
    {
        $this->mockFirewallService([
            'addRule' => [
                'success' => true,
                'message' => 'Rule added successfully',
            ],
            'getUfwStatus' => [
                'installed' => true,
                'enabled' => true,
                'raw_output' => 'Status: active',
            ],
            'getRulesNumbered' => [
                'success' => true,
                'rules' => [
                    ['number' => 1, 'rule' => '8080/tcp ALLOW Anywhere', 'parsed' => ['action' => 'allow']],
                ],
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->set('rulePort', '8080')
            ->set('ruleProtocol', 'tcp')
            ->set('ruleAction', 'allow')
            ->set('ruleDescription', 'Allow HTTP alternate port')
            ->call('addRule')
            ->assertSet('flashMessage', 'Rule added successfully')
            ->assertSet('flashType', 'success')
            ->assertSet('showAddRuleModal', false);

        $this->assertDatabaseHas('firewall_rules', [
            'server_id' => $this->server->id,
            'port' => '8080',
            'protocol' => 'tcp',
            'action' => 'allow',
            'description' => 'Allow HTTP alternate port',
        ]);

        $this->assertDatabaseHas('security_events', [
            'server_id' => $this->server->id,
            'event_type' => SecurityEvent::TYPE_RULE_ADDED,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_add_firewall_rule_with_ip_restriction(): void
    {
        $this->mockFirewallService([
            'addRule' => [
                'success' => true,
                'message' => 'Rule added successfully',
            ],
            'getUfwStatus' => [
                'installed' => true,
                'enabled' => true,
                'raw_output' => 'Status: active',
            ],
            'getRulesNumbered' => [
                'success' => true,
                'rules' => [],
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->set('rulePort', '22')
            ->set('ruleProtocol', 'tcp')
            ->set('ruleAction', 'allow')
            ->set('ruleFromIp', '192.168.1.100')
            ->set('ruleDescription', 'SSH from specific IP')
            ->call('addRule')
            ->assertSet('flashMessage', 'Rule added successfully')
            ->assertSet('flashType', 'success');

        $this->assertDatabaseHas('firewall_rules', [
            'server_id' => $this->server->id,
            'port' => '22',
            'from_ip' => '192.168.1.100',
        ]);
    }

    public function test_add_rule_validates_required_port(): void
    {
        $this->mockFirewallService();

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->set('rulePort', '')
            ->set('ruleProtocol', 'tcp')
            ->set('ruleAction', 'allow')
            ->call('addRule')
            ->assertHasErrors(['rulePort' => 'required']);
    }

    public function test_add_rule_validates_protocol(): void
    {
        $this->mockFirewallService();

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->set('rulePort', '8080')
            ->set('ruleProtocol', 'invalid')
            ->set('ruleAction', 'allow')
            ->call('addRule')
            ->assertHasErrors(['ruleProtocol' => 'in']);
    }

    public function test_add_rule_validates_action(): void
    {
        $this->mockFirewallService();

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->set('rulePort', '8080')
            ->set('ruleProtocol', 'tcp')
            ->set('ruleAction', 'invalid')
            ->call('addRule')
            ->assertHasErrors(['ruleAction' => 'in']);
    }

    public function test_add_rule_validates_port_length(): void
    {
        $this->mockFirewallService();

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->set('rulePort', str_repeat('1', 21))
            ->set('ruleProtocol', 'tcp')
            ->set('ruleAction', 'allow')
            ->call('addRule')
            ->assertHasErrors(['rulePort' => 'max']);
    }

    public function test_add_rule_validates_ip_format_length(): void
    {
        $this->mockFirewallService();

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->set('rulePort', '8080')
            ->set('ruleProtocol', 'tcp')
            ->set('ruleAction', 'allow')
            ->set('ruleFromIp', str_repeat('1', 46))
            ->call('addRule')
            ->assertHasErrors(['ruleFromIp' => 'max']);
    }

    public function test_add_rule_validates_description_length(): void
    {
        $this->mockFirewallService();

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->set('rulePort', '8080')
            ->set('ruleProtocol', 'tcp')
            ->set('ruleAction', 'allow')
            ->set('ruleDescription', str_repeat('a', 256))
            ->call('addRule')
            ->assertHasErrors(['ruleDescription' => 'max']);
    }

    public function test_add_rule_handles_service_failure(): void
    {
        $this->mockFirewallService([
            'addRule' => [
                'success' => false,
                'message' => 'Failed to add rule: Invalid port number',
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->set('rulePort', '99999')
            ->set('ruleProtocol', 'tcp')
            ->set('ruleAction', 'allow')
            ->call('addRule')
            ->assertSet('flashMessage', 'Failed to add rule: Invalid port number')
            ->assertSet('flashType', 'error');
    }

    public function test_can_delete_firewall_rule_successfully(): void
    {
        $this->mockFirewallService([
            'deleteRule' => [
                'success' => true,
                'message' => 'Rule deleted successfully',
            ],
            'getUfwStatus' => [
                'installed' => true,
                'enabled' => true,
                'raw_output' => 'Status: active',
            ],
            'getRulesNumbered' => [
                'success' => true,
                'rules' => [],
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->call('deleteRule', 1)
            ->assertSet('flashMessage', 'Rule deleted successfully')
            ->assertSet('flashType', 'success');

        $this->assertDatabaseHas('security_events', [
            'server_id' => $this->server->id,
            'event_type' => SecurityEvent::TYPE_RULE_DELETED,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_delete_rule_handles_failure(): void
    {
        $this->mockFirewallService([
            'deleteRule' => [
                'success' => false,
                'message' => 'Failed to delete rule: Rule not found',
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->call('deleteRule', 999)
            ->assertSet('flashMessage', 'Failed to delete rule: Rule not found')
            ->assertSet('flashType', 'error');
    }

    public function test_can_install_ufw_successfully(): void
    {
        $this->server->update(['ufw_installed' => false]);

        $this->mockFirewallService([
            'installUfw' => [
                'success' => true,
                'message' => 'UFW installed successfully',
            ],
            'getUfwStatus' => [
                'installed' => true,
                'enabled' => false,
                'raw_output' => 'Status: inactive',
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->call('installUfw')
            ->assertSet('flashMessage', 'UFW installed successfully')
            ->assertSet('flashType', 'success')
            ->assertSet('ufwInstalled', true);

        $this->server->refresh();
        $this->assertTrue($this->server->ufw_installed);
    }

    public function test_install_ufw_handles_failure(): void
    {
        $this->mockFirewallService([
            'installUfw' => [
                'success' => false,
                'message' => 'Failed to install UFW: Permission denied',
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->call('installUfw')
            ->assertSet('flashMessage', 'Failed to install UFW: Permission denied')
            ->assertSet('flashType', 'error');
    }

    public function test_component_handles_connection_errors_gracefully(): void
    {
        $this->mockFirewallService([
            'getUfwStatus' => [
                'installed' => false,
                'enabled' => false,
                'rules' => [],
                'error' => 'Connection timeout',
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->assertSet('flashMessage', 'Connection issue: Connection timeout')
            ->assertSet('flashType', 'error');
    }

    public function test_component_handles_exceptions_in_load_firewall_status(): void
    {
        $this->instance(
            FirewallService::class,
            Mockery::mock(FirewallService::class, function (MockInterface $mock): void {
                $mock->shouldReceive('getUfwStatus')
                    ->andThrow(new \RuntimeException('Unexpected error'));
            })
        );

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->assertSet('flashMessage', 'Failed to load firewall status: Unexpected error')
            ->assertSet('flashType', 'error');
    }

    public function test_component_handles_exceptions_in_enable_firewall(): void
    {
        $this->instance(
            FirewallService::class,
            Mockery::mock(FirewallService::class, function (MockInterface $mock): void {
                $mock->shouldReceive('getUfwStatus')
                    ->andReturn([
                        'installed' => true,
                        'enabled' => false,
                        'rules' => [],
                    ]);
                $mock->shouldReceive('enableUfw')
                    ->andThrow(new \RuntimeException('Unexpected error'));
            })
        );

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->call('enableFirewall')
            ->assertSet('flashMessage', 'Failed to enable firewall: Unexpected error')
            ->assertSet('flashType', 'error');
    }

    public function test_component_handles_exceptions_in_add_rule(): void
    {
        $this->instance(
            FirewallService::class,
            Mockery::mock(FirewallService::class, function (MockInterface $mock): void {
                $mock->shouldReceive('getUfwStatus')
                    ->andReturn([
                        'installed' => true,
                        'enabled' => true,
                        'rules' => [],
                    ]);
                $mock->shouldReceive('addRule')
                    ->andThrow(new \RuntimeException('Unexpected error'));
            })
        );

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->set('rulePort', '8080')
            ->set('ruleProtocol', 'tcp')
            ->set('ruleAction', 'allow')
            ->call('addRule')
            ->assertSet('flashMessage', 'Failed to add rule: Unexpected error')
            ->assertSet('flashType', 'error');
    }

    public function test_firewall_rules_are_displayed_when_enabled(): void
    {
        $this->mockFirewallService([
            'getUfwStatus' => [
                'installed' => true,
                'enabled' => true,
                'raw_output' => 'Status: active',
            ],
            'getRulesNumbered' => [
                'success' => true,
                'rules' => [
                    ['number' => 1, 'rule' => '22/tcp ALLOW Anywhere', 'parsed' => ['action' => 'allow']],
                    ['number' => 2, 'rule' => '80/tcp ALLOW Anywhere', 'parsed' => ['action' => 'allow']],
                    ['number' => 3, 'rule' => '443/tcp ALLOW Anywhere', 'parsed' => ['action' => 'allow']],
                ],
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->assertSet('ufwEnabled', true)
            ->assertCount('rules', 3)
            ->assertSee('22/tcp ALLOW Anywhere')
            ->assertSee('80/tcp ALLOW Anywhere')
            ->assertSee('443/tcp ALLOW Anywhere');
    }

    public function test_no_rules_displayed_when_firewall_disabled(): void
    {
        $this->mockFirewallService([
            'getUfwStatus' => [
                'installed' => true,
                'enabled' => false,
                'raw_output' => 'Status: inactive',
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->assertSet('ufwEnabled', false)
            ->assertCount('rules', 0);
    }

    public function test_can_add_deny_rule(): void
    {
        $this->mockFirewallService([
            'addRule' => [
                'success' => true,
                'message' => 'Rule added successfully',
            ],
            'getUfwStatus' => [
                'installed' => true,
                'enabled' => true,
                'raw_output' => 'Status: active',
            ],
            'getRulesNumbered' => [
                'success' => true,
                'rules' => [],
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->set('rulePort', '23')
            ->set('ruleProtocol', 'tcp')
            ->set('ruleAction', 'deny')
            ->set('ruleDescription', 'Block telnet')
            ->call('addRule')
            ->assertSet('flashMessage', 'Rule added successfully')
            ->assertSet('flashType', 'success');

        $this->assertDatabaseHas('firewall_rules', [
            'server_id' => $this->server->id,
            'port' => '23',
            'action' => 'deny',
        ]);
    }

    public function test_can_add_reject_rule(): void
    {
        $this->mockFirewallService([
            'addRule' => [
                'success' => true,
                'message' => 'Rule added successfully',
            ],
            'getUfwStatus' => [
                'installed' => true,
                'enabled' => true,
                'raw_output' => 'Status: active',
            ],
            'getRulesNumbered' => [
                'success' => true,
                'rules' => [],
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->set('rulePort', '445')
            ->set('ruleProtocol', 'tcp')
            ->set('ruleAction', 'reject')
            ->call('addRule')
            ->assertSet('flashMessage', 'Rule added successfully')
            ->assertSet('flashType', 'success');

        $this->assertDatabaseHas('firewall_rules', [
            'server_id' => $this->server->id,
            'port' => '445',
            'action' => 'reject',
        ]);
    }

    public function test_can_add_limit_rule(): void
    {
        $this->mockFirewallService([
            'addRule' => [
                'success' => true,
                'message' => 'Rule added successfully',
            ],
            'getUfwStatus' => [
                'installed' => true,
                'enabled' => true,
                'raw_output' => 'Status: active',
            ],
            'getRulesNumbered' => [
                'success' => true,
                'rules' => [],
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->set('rulePort', '22')
            ->set('ruleProtocol', 'tcp')
            ->set('ruleAction', 'limit')
            ->set('ruleDescription', 'Rate limit SSH')
            ->call('addRule')
            ->assertSet('flashMessage', 'Rule added successfully')
            ->assertSet('flashType', 'success');

        $this->assertDatabaseHas('firewall_rules', [
            'server_id' => $this->server->id,
            'port' => '22',
            'action' => 'limit',
        ]);
    }

    public function test_can_add_udp_rule(): void
    {
        $this->mockFirewallService([
            'addRule' => [
                'success' => true,
                'message' => 'Rule added successfully',
            ],
            'getUfwStatus' => [
                'installed' => true,
                'enabled' => true,
                'raw_output' => 'Status: active',
            ],
            'getRulesNumbered' => [
                'success' => true,
                'rules' => [],
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->set('rulePort', '53')
            ->set('ruleProtocol', 'udp')
            ->set('ruleAction', 'allow')
            ->set('ruleDescription', 'Allow DNS')
            ->call('addRule')
            ->assertSet('flashMessage', 'Rule added successfully')
            ->assertSet('flashType', 'success');

        $this->assertDatabaseHas('firewall_rules', [
            'server_id' => $this->server->id,
            'port' => '53',
            'protocol' => 'udp',
        ]);
    }

    public function test_can_add_any_protocol_rule(): void
    {
        $this->mockFirewallService([
            'addRule' => [
                'success' => true,
                'message' => 'Rule added successfully',
            ],
            'getUfwStatus' => [
                'installed' => true,
                'enabled' => true,
                'raw_output' => 'Status: active',
            ],
            'getRulesNumbered' => [
                'success' => true,
                'rules' => [],
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->set('rulePort', '1234')
            ->set('ruleProtocol', 'any')
            ->set('ruleAction', 'allow')
            ->call('addRule')
            ->assertSet('flashMessage', 'Rule added successfully')
            ->assertSet('flashType', 'success');

        $this->assertDatabaseHas('firewall_rules', [
            'server_id' => $this->server->id,
            'port' => '1234',
            'protocol' => 'any',
        ]);
    }

    public function test_component_displays_raw_output_for_debugging(): void
    {
        $rawOutput = 'Status: active
Logging: on (low)
Default: deny (incoming), allow (outgoing), disabled (routed)
New profiles: skip

To                         Action      From
--                         ------      ----
22/tcp                     ALLOW       Anywhere';

        $this->mockFirewallService([
            'getUfwStatus' => [
                'installed' => true,
                'enabled' => true,
                'raw_output' => $rawOutput,
            ],
            'getRulesNumbered' => [
                'success' => true,
                'rules' => [],
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->assertSet('rawOutput', $rawOutput);
    }

    public function test_load_firewall_status_refreshes_server_model(): void
    {
        $this->mockFirewallService([
            'getUfwStatus' => [
                'installed' => true,
                'enabled' => true,
                'raw_output' => 'Status: active',
            ],
            'getRulesNumbered' => [
                'success' => true,
                'rules' => [],
            ],
        ]);

        $this->server->update(['name' => 'Old Name']);

        // Simulate external update
        Server::where('id', $this->server->id)->update(['name' => 'New Name']);

        Livewire::actingAs($this->user)
            ->test(FirewallManager::class, ['server' => $this->server])
            ->call('loadFirewallStatus');

        $this->server->refresh();
        $this->assertEquals('New Name', $this->server->name);
    }

    /**
     * Mock the FirewallService with predefined responses
     *
     * @param  array<string, array<string, mixed>>  $responses
     */
    private function mockFirewallService(array $responses = []): void
    {
        $this->instance(
            FirewallService::class,
            Mockery::mock(FirewallService::class, function (MockInterface $mock) use ($responses): void {
                $defaultGetUfwStatus = [
                    'installed' => true,
                    'enabled' => false,
                    'rules' => [],
                    'raw_output' => 'Status: inactive',
                ];

                $mock->shouldReceive('getUfwStatus')
                    ->andReturn($responses['getUfwStatus'] ?? $defaultGetUfwStatus)
                    ->byDefault();

                if (isset($responses['getRulesNumbered'])) {
                    $mock->shouldReceive('getRulesNumbered')
                        ->andReturn($responses['getRulesNumbered']);
                }

                if (isset($responses['enableUfw'])) {
                    $mock->shouldReceive('enableUfw')
                        ->andReturn($responses['enableUfw']);
                }

                if (isset($responses['disableUfw'])) {
                    $mock->shouldReceive('disableUfw')
                        ->andReturn($responses['disableUfw']);
                }

                if (isset($responses['addRule'])) {
                    $mock->shouldReceive('addRule')
                        ->andReturn($responses['addRule']);
                }

                if (isset($responses['deleteRule'])) {
                    $mock->shouldReceive('deleteRule')
                        ->andReturn($responses['deleteRule']);
                }

                if (isset($responses['installUfw'])) {
                    $mock->shouldReceive('installUfw')
                        ->andReturn($responses['installUfw']);
                }
            })
        );
    }
}
