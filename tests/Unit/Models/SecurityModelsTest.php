<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\FirewallRule;
use App\Models\SecurityEvent;
use App\Models\Server;
use App\Models\User;
use Tests\TestCase;

class SecurityModelsTest extends TestCase
{
    // ========================
    // FirewallRule Model Tests
    // ========================

    /** @test */
    public function firewall_rule_can_be_created_with_factory(): void
    {
        $rule = FirewallRule::factory()->create();

        $this->assertInstanceOf(FirewallRule::class, $rule);
        $this->assertDatabaseHas('firewall_rules', [
            'id' => $rule->id,
        ]);
    }

    /** @test */
    public function firewall_rule_belongs_to_server(): void
    {
        $server = Server::factory()->create();
        $rule = FirewallRule::factory()->create(['server_id' => $server->id]);

        $this->assertInstanceOf(Server::class, $rule->server);
        $this->assertEquals($server->id, $rule->server->id);
    }

    /** @test */
    public function firewall_rule_casts_is_active_as_boolean(): void
    {
        $rule = FirewallRule::factory()->create(['is_active' => true]);

        $this->assertTrue($rule->is_active);
        $this->assertIsBool($rule->is_active);
    }

    /** @test */
    public function firewall_rule_casts_priority_as_integer(): void
    {
        $rule = FirewallRule::factory()->create(['priority' => 100]);

        $this->assertIsInt($rule->priority);
        $this->assertEquals(100, $rule->priority);
    }

    /** @test */
    public function firewall_rule_display_name_includes_port_and_protocol(): void
    {
        $rule = FirewallRule::factory()->create([
            'port' => '80',
            'protocol' => 'tcp',
        ]);

        $this->assertStringContainsString('80/tcp', $rule->display_name);
    }

    /** @test */
    public function firewall_rule_display_name_includes_from_ip(): void
    {
        $rule = FirewallRule::factory()->create([
            'port' => '22',
            'protocol' => 'tcp',
            'from_ip' => '192.168.1.100',
        ]);

        $this->assertStringContainsString('22/tcp', $rule->display_name);
        $this->assertStringContainsString('from 192.168.1.100', $rule->display_name);
    }

    /** @test */
    public function firewall_rule_display_name_returns_any_when_no_criteria(): void
    {
        $rule = FirewallRule::factory()->create([
            'port' => null,
            'from_ip' => null,
        ]);

        $this->assertEquals('Any', $rule->display_name);
    }

    /** @test */
    public function firewall_rule_to_ufw_command_generates_basic_allow_rule(): void
    {
        $rule = FirewallRule::factory()->create([
            'action' => 'allow',
            'direction' => 'in',
            'port' => '80',
            'protocol' => 'tcp',
        ]);

        $command = $rule->toUfwCommand();

        $this->assertStringContainsString('ufw allow', $command);
        $this->assertStringContainsString('to any port 80', $command);
        $this->assertStringContainsString('proto tcp', $command);
    }

    /** @test */
    public function firewall_rule_to_ufw_command_includes_from_ip(): void
    {
        $rule = FirewallRule::factory()->create([
            'action' => 'allow',
            'from_ip' => '192.168.1.0/24',
            'port' => '22',
            'protocol' => 'tcp',
        ]);

        $command = $rule->toUfwCommand();

        $this->assertStringContainsString('from 192.168.1.0/24', $command);
    }

    /** @test */
    public function firewall_rule_to_ufw_command_handles_outbound_direction(): void
    {
        $rule = FirewallRule::factory()->create([
            'action' => 'allow',
            'direction' => 'out',
            'port' => '443',
            'protocol' => 'tcp',
        ]);

        $command = $rule->toUfwCommand();

        $this->assertStringContainsString('ufw allow out', $command);
    }

    /** @test */
    public function firewall_rule_to_ufw_command_handles_any_protocol(): void
    {
        $rule = FirewallRule::factory()->create([
            'action' => 'deny',
            'protocol' => 'any',
            'port' => '8080',
        ]);

        $command = $rule->toUfwCommand();

        $this->assertStringNotContainsString('proto any', $command);
    }

    /** @test */
    public function firewall_rule_to_ufw_command_handles_deny_action(): void
    {
        $rule = FirewallRule::factory()->create([
            'action' => 'deny',
            'port' => '23',
            'protocol' => 'tcp',
        ]);

        $command = $rule->toUfwCommand();

        $this->assertStringContainsString('ufw deny', $command);
    }

    // ========================
    // SecurityEvent Model Tests
    // ========================

    /** @test */
    public function security_event_can_be_created_with_factory(): void
    {
        $event = SecurityEvent::factory()->create();

        $this->assertInstanceOf(SecurityEvent::class, $event);
        $this->assertDatabaseHas('security_events', [
            'id' => $event->id,
        ]);
    }

    /** @test */
    public function security_event_belongs_to_server(): void
    {
        $server = Server::factory()->create();
        $event = SecurityEvent::factory()->create(['server_id' => $server->id]);

        $this->assertInstanceOf(Server::class, $event->server);
        $this->assertEquals($server->id, $event->server->id);
    }

    /** @test */
    public function security_event_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $event = SecurityEvent::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $event->user);
        $this->assertEquals($user->id, $event->user->id);
    }

    /** @test */
    public function security_event_casts_metadata_as_array(): void
    {
        $metadata = ['key' => 'value', 'nested' => ['data' => 'test']];
        $event = SecurityEvent::factory()->create(['metadata' => $metadata]);

        $this->assertIsArray($event->metadata);
        $this->assertEquals($metadata, $event->metadata);
    }

    /** @test */
    public function security_event_get_event_type_label_returns_correct_labels(): void
    {
        $firewallEnabled = SecurityEvent::factory()->create(['event_type' => SecurityEvent::TYPE_FIREWALL_ENABLED]);
        $this->assertEquals('Firewall Enabled', $firewallEnabled->getEventTypeLabel());

        $firewallDisabled = SecurityEvent::factory()->create(['event_type' => SecurityEvent::TYPE_FIREWALL_DISABLED]);
        $this->assertEquals('Firewall Disabled', $firewallDisabled->getEventTypeLabel());

        $ruleAdded = SecurityEvent::factory()->create(['event_type' => SecurityEvent::TYPE_RULE_ADDED]);
        $this->assertEquals('Rule Added', $ruleAdded->getEventTypeLabel());

        $ruleDeleted = SecurityEvent::factory()->create(['event_type' => SecurityEvent::TYPE_RULE_DELETED]);
        $this->assertEquals('Rule Deleted', $ruleDeleted->getEventTypeLabel());

        $ipBanned = SecurityEvent::factory()->create(['event_type' => SecurityEvent::TYPE_IP_BANNED]);
        $this->assertEquals('IP Banned', $ipBanned->getEventTypeLabel());

        $ipUnbanned = SecurityEvent::factory()->create(['event_type' => SecurityEvent::TYPE_IP_UNBANNED]);
        $this->assertEquals('IP Unbanned', $ipUnbanned->getEventTypeLabel());

        $sshChanged = SecurityEvent::factory()->create(['event_type' => SecurityEvent::TYPE_SSH_CONFIG_CHANGED]);
        $this->assertEquals('SSH Config Changed', $sshChanged->getEventTypeLabel());

        $securityScan = SecurityEvent::factory()->create(['event_type' => SecurityEvent::TYPE_SECURITY_SCAN]);
        $this->assertEquals('Security Scan', $securityScan->getEventTypeLabel());
    }

    /** @test */
    public function security_event_get_event_type_label_handles_custom_types(): void
    {
        $event = SecurityEvent::factory()->create(['event_type' => 'custom_security_event']);

        $this->assertEquals('Custom security event', $event->getEventTypeLabel());
    }

    /** @test */
    public function security_event_event_type_color_returns_correct_colors(): void
    {
        $firewallEnabled = SecurityEvent::factory()->create(['event_type' => SecurityEvent::TYPE_FIREWALL_ENABLED]);
        $this->assertEquals('green', $firewallEnabled->event_type_color);

        $ruleAdded = SecurityEvent::factory()->create(['event_type' => SecurityEvent::TYPE_RULE_ADDED]);
        $this->assertEquals('green', $ruleAdded->event_type_color);

        $firewallDisabled = SecurityEvent::factory()->create(['event_type' => SecurityEvent::TYPE_FIREWALL_DISABLED]);
        $this->assertEquals('red', $firewallDisabled->event_type_color);

        $ruleDeleted = SecurityEvent::factory()->create(['event_type' => SecurityEvent::TYPE_RULE_DELETED]);
        $this->assertEquals('red', $ruleDeleted->event_type_color);

        $ipBanned = SecurityEvent::factory()->create(['event_type' => SecurityEvent::TYPE_IP_BANNED]);
        $this->assertEquals('orange', $ipBanned->event_type_color);

        $ipUnbanned = SecurityEvent::factory()->create(['event_type' => SecurityEvent::TYPE_IP_UNBANNED]);
        $this->assertEquals('yellow', $ipUnbanned->event_type_color);

        $sshChanged = SecurityEvent::factory()->create(['event_type' => SecurityEvent::TYPE_SSH_CONFIG_CHANGED]);
        $this->assertEquals('blue', $sshChanged->event_type_color);

        $securityScan = SecurityEvent::factory()->create(['event_type' => SecurityEvent::TYPE_SECURITY_SCAN]);
        $this->assertEquals('purple', $securityScan->event_type_color);

        $unknown = SecurityEvent::factory()->create(['event_type' => 'unknown_type']);
        $this->assertEquals('gray', $unknown->event_type_color);
    }

    /** @test */
    public function security_event_defines_type_constants(): void
    {
        $this->assertEquals('firewall_enabled', SecurityEvent::TYPE_FIREWALL_ENABLED);
        $this->assertEquals('firewall_disabled', SecurityEvent::TYPE_FIREWALL_DISABLED);
        $this->assertEquals('rule_added', SecurityEvent::TYPE_RULE_ADDED);
        $this->assertEquals('rule_deleted', SecurityEvent::TYPE_RULE_DELETED);
        $this->assertEquals('ip_banned', SecurityEvent::TYPE_IP_BANNED);
        $this->assertEquals('ip_unbanned', SecurityEvent::TYPE_IP_UNBANNED);
        $this->assertEquals('ssh_config_changed', SecurityEvent::TYPE_SSH_CONFIG_CHANGED);
        $this->assertEquals('security_scan', SecurityEvent::TYPE_SECURITY_SCAN);
    }
}
