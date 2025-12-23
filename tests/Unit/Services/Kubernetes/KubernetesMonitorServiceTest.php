<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Kubernetes;

use App\Models\Project;
use App\Services\Kubernetes\KubernetesMonitorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class KubernetesMonitorServiceTest extends TestCase
{
    use RefreshDatabase;

    private KubernetesMonitorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new KubernetesMonitorService();
    }

    public function test_get_pod_status_returns_parsed_pods(): void
    {
        $podsJson = json_encode([
            'items' => [
                [
                    'metadata' => [
                        'name' => 'test-pod-abc123',
                        'creationTimestamp' => now()->subHours(2)->toIso8601String(),
                    ],
                    'status' => [
                        'phase' => 'Running',
                        'conditions' => [
                            ['type' => 'Ready', 'status' => 'True'],
                        ],
                        'containerStatuses' => [
                            ['restartCount' => 0],
                        ],
                    ],
                    'spec' => [
                        'nodeName' => 'node-1',
                    ],
                ],
            ],
        ]);

        Process::fake([
            '*kubectl*get pods*' => Process::result(output: $podsJson),
        ]);

        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $pods = $this->service->getPodStatus($project);

        $this->assertCount(1, $pods);
        $this->assertEquals('test-pod-abc123', $pods[0]['name']);
        $this->assertEquals('Running', $pods[0]['status']);
        $this->assertTrue($pods[0]['ready']);
        $this->assertEquals(0, $pods[0]['restarts']);
        $this->assertEquals('node-1', $pods[0]['node']);
    }

    public function test_get_pod_status_returns_empty_on_failure(): void
    {
        Process::fake([
            '*' => Process::result(exitCode: 1, errorOutput: 'Error'),
        ]);

        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $pods = $this->service->getPodStatus($project);

        $this->assertEmpty($pods);
    }

    public function test_get_service_endpoints_returns_endpoints(): void
    {
        $serviceJson = json_encode([
            'status' => [
                'loadBalancer' => [
                    'ingress' => [
                        ['ip' => '10.0.0.1'],
                    ],
                ],
            ],
        ]);

        $ingressJson = json_encode([
            'spec' => [
                'rules' => [
                    ['host' => 'test.example.com'],
                ],
            ],
        ]);

        Process::fake([
            '*get service*' => Process::result(output: $serviceJson),
            '*get ingress*' => Process::result(output: $ingressJson),
        ]);

        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $endpoints = $this->service->getServiceEndpoints($project);

        $this->assertContains('10.0.0.1', $endpoints);
        $this->assertContains('https://test.example.com', $endpoints);
    }

    public function test_scale_deployment(): void
    {
        Process::fake([
            '*scale deployment*' => Process::result(output: 'deployment.apps/test-project-deployment scaled'),
        ]);

        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $result = $this->service->scaleDeployment($project, 5);

        $this->assertTrue($result['success']);
        $this->assertEquals(5, $result['new_replicas']);

        Process::assertRan(function ($process) {
            return str_contains($process->command, 'scale deployment') &&
                   str_contains($process->command, '--replicas=5');
        });
    }

    public function test_execute_in_pod(): void
    {
        $podsJson = json_encode([
            'items' => [
                [
                    'metadata' => ['name' => 'test-pod-123'],
                    'status' => [
                        'phase' => 'Running',
                        'conditions' => [['type' => 'Ready', 'status' => 'True']],
                        'containerStatuses' => [['restartCount' => 0]],
                    ],
                    'spec' => ['nodeName' => 'node-1'],
                ],
            ],
        ]);

        Process::fake([
            '*get pods*' => Process::result(output: $podsJson),
            '*exec*' => Process::result(output: 'Command executed'),
        ]);

        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $result = $this->service->executeInPod($project, 'php artisan migrate');

        $this->assertTrue($result['success']);
        $this->assertEquals('Command executed', $result['output']);
        $this->assertEquals('test-pod-123', $result['pod']);
    }

    public function test_execute_in_pod_with_specific_pod_name(): void
    {
        Process::fake([
            '*exec*' => Process::result(output: 'Success'),
        ]);

        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $result = $this->service->executeInPod($project, 'echo hello', 'my-specific-pod');

        $this->assertTrue($result['success']);
        $this->assertEquals('my-specific-pod', $result['pod']);
    }

    public function test_execute_in_pod_throws_when_no_running_pods(): void
    {
        $podsJson = json_encode(['items' => []]);

        Process::fake([
            '*get pods*' => Process::result(output: $podsJson),
        ]);

        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No running pods found');

        $this->service->executeInPod($project, 'php artisan migrate');
    }

    public function test_get_deployment_logs(): void
    {
        Process::fake([
            '*logs deployment*' => Process::result(output: 'Log line 1\nLog line 2\nLog line 3'),
        ]);

        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $result = $this->service->getDeploymentLogs($project, 50);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Log line 1', $result['logs']);

        Process::assertRan(function ($process) {
            return str_contains($process->command, '--tail=50');
        });
    }

    public function test_get_pod_logs(): void
    {
        Process::fake([
            '*logs test-pod*' => Process::result(output: 'Pod log output'),
        ]);

        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $result = $this->service->getPodLogs($project, 'test-pod-123', 100);

        $this->assertTrue($result['success']);
        $this->assertEquals('Pod log output', $result['logs']);
    }

    public function test_get_deployment_status(): void
    {
        $deploymentJson = json_encode([
            'metadata' => ['name' => 'test-project-deployment'],
            'status' => [
                'replicas' => 3,
                'readyReplicas' => 3,
                'availableReplicas' => 3,
                'updatedReplicas' => 3,
                'conditions' => [
                    ['type' => 'Available', 'status' => 'True'],
                ],
            ],
        ]);

        Process::fake([
            '*get deployment*' => Process::result(output: $deploymentJson),
        ]);

        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $result = $this->service->getDeploymentStatus($project);

        $this->assertTrue($result['success']);
        $this->assertEquals('test-project-deployment', $result['name']);
        $this->assertEquals(3, $result['replicas']);
        $this->assertEquals(3, $result['ready_replicas']);
        $this->assertEquals(3, $result['available_replicas']);
    }

    public function test_get_deployment_status_returns_error_on_failure(): void
    {
        Process::fake([
            '*' => Process::result(exitCode: 1, errorOutput: 'Deployment not found'),
        ]);

        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $result = $this->service->getDeploymentStatus($project);

        $this->assertFalse($result['success']);
        $this->assertEquals('Deployment not found', $result['error']);
    }

    public function test_get_resource_usage(): void
    {
        $topOutput = "test-pod-1   100m   256Mi\ntest-pod-2   200m   512Mi";

        Process::fake([
            '*top pods*' => Process::result(output: $topOutput),
        ]);

        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $usage = $this->service->getResourceUsage($project);

        $this->assertCount(2, $usage);
        $this->assertEquals('test-pod-1', $usage[0]['pod']);
        $this->assertEquals('100m', $usage[0]['cpu']);
        $this->assertEquals('256Mi', $usage[0]['memory']);
    }

    public function test_get_resource_usage_returns_empty_on_failure(): void
    {
        Process::fake([
            '*' => Process::result(exitCode: 1),
        ]);

        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $usage = $this->service->getResourceUsage($project);

        $this->assertEmpty($usage);
    }

    public function test_get_events(): void
    {
        $eventsJson = json_encode([
            'items' => [
                [
                    'type' => 'Normal',
                    'reason' => 'Scheduled',
                    'message' => 'Pod scheduled on node-1',
                    'count' => 1,
                    'firstTimestamp' => '2025-01-01T10:00:00Z',
                    'lastTimestamp' => '2025-01-01T10:00:00Z',
                    'source' => ['component' => 'scheduler'],
                ],
                [
                    'type' => 'Warning',
                    'reason' => 'FailedMount',
                    'message' => 'Unable to mount volume',
                    'count' => 3,
                    'firstTimestamp' => '2025-01-01T09:00:00Z',
                    'lastTimestamp' => '2025-01-01T10:30:00Z',
                    'source' => ['component' => 'kubelet'],
                ],
            ],
        ]);

        Process::fake([
            '*get events*' => Process::result(output: $eventsJson),
        ]);

        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $events = $this->service->getEvents($project);

        $this->assertCount(2, $events);
        $this->assertEquals('Normal', $events[0]['type']);
        $this->assertEquals('Scheduled', $events[0]['reason']);
        $this->assertEquals('scheduler', $events[0]['source']);
    }

    public function test_get_events_with_limit(): void
    {
        $eventsJson = json_encode([
            'items' => array_fill(0, 100, [
                'type' => 'Normal',
                'reason' => 'Test',
                'message' => 'Test event',
                'count' => 1,
                'source' => ['component' => 'test'],
            ]),
        ]);

        Process::fake([
            '*' => Process::result(output: $eventsJson),
        ]);

        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $events = $this->service->getEvents($project, 10);

        $this->assertCount(10, $events);
    }

    public function test_get_kubectl_path(): void
    {
        $path = $this->service->getKubectlPath();

        $this->assertEquals('/usr/local/bin/kubectl', $path);
    }
}
