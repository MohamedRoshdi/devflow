<?php

declare(strict_types=1);

namespace Tests\Unit\Models;


use PHPUnit\Framework\Attributes\Test;
use App\Models\Deployment;
use App\Models\Tenant;
use App\Models\TenantDeployment;
use Tests\TestCase;

class TenantModelsTest extends TestCase
{
    // ========================
    // TenantDeployment Model Tests
    // ========================

    #[Test]
    public function tenant_deployment_can_be_created_with_factory(): void
    {
        $tenantDeployment = TenantDeployment::factory()->create();

        $this->assertInstanceOf(TenantDeployment::class, $tenantDeployment);
        $this->assertDatabaseHas('tenant_deployments', [
            'id' => $tenantDeployment->id,
        ]);
    }

    #[Test]
    public function tenant_deployment_belongs_to_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $tenantDeployment = TenantDeployment::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertInstanceOf(Tenant::class, $tenantDeployment->tenant);
        $this->assertEquals($tenant->id, $tenantDeployment->tenant->id);
    }

    #[Test]
    public function tenant_deployment_belongs_to_deployment(): void
    {
        $deployment = Deployment::factory()->create();
        $tenantDeployment = TenantDeployment::factory()->create(['deployment_id' => $deployment->id]);

        $this->assertInstanceOf(Deployment::class, $tenantDeployment->deployment);
        $this->assertEquals($deployment->id, $tenantDeployment->deployment->id);
    }

    #[Test]
    public function tenant_deployment_has_fillable_attributes(): void
    {
        $tenantDeployment = TenantDeployment::factory()->create([
            'status' => 'success',
            'output' => 'Deployment completed successfully',
        ]);

        $this->assertEquals('success', $tenantDeployment->status);
        $this->assertEquals('Deployment completed successfully', $tenantDeployment->output);
    }

    #[Test]
    public function tenant_deployment_can_store_deployment_output(): void
    {
        $output = "Starting deployment...\nRunning migrations...\nDeployment complete.";
        $tenantDeployment = TenantDeployment::factory()->create(['output' => $output]);

        $this->assertEquals($output, $tenantDeployment->output);
    }

    #[Test]
    public function tenant_deployment_can_have_different_statuses(): void
    {
        $pending = TenantDeployment::factory()->create(['status' => 'pending']);
        $this->assertEquals('pending', $pending->status);

        $running = TenantDeployment::factory()->create(['status' => 'running']);
        $this->assertEquals('running', $running->status);

        $success = TenantDeployment::factory()->create(['status' => 'success']);
        $this->assertEquals('success', $success->status);

        $failed = TenantDeployment::factory()->create(['status' => 'failed']);
        $this->assertEquals('failed', $failed->status);
    }

    #[Test]
    public function tenant_deployment_has_timestamps(): void
    {
        $tenantDeployment = TenantDeployment::factory()->create();

        $this->assertNotNull($tenantDeployment->created_at);
        $this->assertNotNull($tenantDeployment->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $tenantDeployment->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $tenantDeployment->updated_at);
    }

    #[Test]
    public function multiple_tenant_deployments_can_belong_to_same_deployment(): void
    {
        $deployment = Deployment::factory()->create();
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $tenantDeployment1 = TenantDeployment::factory()->create([
            'deployment_id' => $deployment->id,
            'tenant_id' => $tenant1->id,
        ]);

        $tenantDeployment2 = TenantDeployment::factory()->create([
            'deployment_id' => $deployment->id,
            'tenant_id' => $tenant2->id,
        ]);

        $this->assertEquals($deployment->id, $tenantDeployment1->deployment->id);
        $this->assertEquals($deployment->id, $tenantDeployment2->deployment->id);
        $this->assertNotEquals($tenantDeployment1->tenant_id, $tenantDeployment2->tenant_id);
    }

    #[Test]
    public function tenant_deployment_can_be_updated(): void
    {
        $tenantDeployment = TenantDeployment::factory()->create([
            'status' => 'pending',
            'output' => null,
        ]);

        $tenantDeployment->update([
            'status' => 'success',
            'output' => 'Deployment completed',
        ]);

        $this->assertEquals('success', $tenantDeployment->status);
        $this->assertEquals('Deployment completed', $tenantDeployment->output);
    }
}
