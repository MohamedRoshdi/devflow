<?php

namespace Tests\Unit\Services;

use App\Services\DockerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;
use Tests\Traits\CreatesProjects;
use Tests\Traits\CreatesServers;
use Tests\Traits\MocksSSH;

class DockerServiceTest extends TestCase
{
    use CreatesProjects, CreatesServers, MocksSSH, RefreshDatabase;

    protected DockerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DockerService;
    }

    // ==========================================
    // DOCKER INSTALLATION TESTS
    // ==========================================

    /** @test */
    public function it_checks_docker_installation_on_server(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        Process::fake([
            '*docker --version*' => Process::result(
                output: 'Docker version 24.0.5, build ced0996'
            ),
        ]);

        // Act
        $result = $this->service->checkDockerInstallation($server);

        // Assert
        $this->assertTrue($result['installed']);
        $this->assertEquals('24.0.5', $result['version']);
    }

    /** @test */
    public function it_detects_docker_not_installed(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        Process::fake([
            '*docker --version*' => Process::result(
                output: '',
                errorOutput: 'docker: command not found',
                exitCode: 127
            ),
        ]);

        // Act
        $result = $this->service->checkDockerInstallation($server);

        // Assert
        $this->assertFalse($result['installed']);
    }

    /** @test */
    public function it_handles_docker_installation_check_exception(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockSshFailure('Connection timeout');

        // Act
        $result = $this->service->checkDockerInstallation($server);

        // Assert
        $this->assertFalse($result['installed']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_installs_docker_on_server(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        Process::fake([
            '*get-docker.sh*' => Process::result(
                output: 'Docker installed successfully'
            ),
        ]);

        // Act
        $result = $this->service->installDocker($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('installed successfully', $result['output']);
    }

    /** @test */
    public function it_handles_docker_installation_failure(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        Process::fake([
            '*get-docker.sh*' => Process::result(
                output: '',
                errorOutput: 'Installation failed: network error',
                exitCode: 1
            ),
        ]);

        // Act
        $result = $this->service->installDocker($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('network error', $result['error']);
    }

    // ==========================================
    // CONTAINER BUILD TESTS
    // ==========================================

    /** @test */
    public function it_builds_container_with_docker_compose(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createProjectForServer($server);

        Process::fake([
            '*test -f*docker-compose.yml*' => Process::result(output: 'compose'),
            '*docker compose build*' => Process::result(
                output: 'Building services...'
            ),
        ]);

        // Act
        $result = $this->service->buildContainer($project);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('docker-compose', $result['type']);
    }

    /** @test */
    public function it_builds_standalone_container_with_dockerfile(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createProjectForServer($server);

        Process::fake([
            '*test -f*docker-compose.yml*' => Process::result(output: 'standalone'),
            '*if \[ -f Dockerfile \]*' => Process::result(output: 'Dockerfile'),
            '*docker build*' => Process::result(
                output: 'Successfully built abc123'
            ),
        ]);

        // Act
        $result = $this->service->buildContainer($project);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('standalone', $result['type']);
    }

    /** @test */
    public function it_builds_container_with_production_dockerfile(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createProjectForServer($server);

        Process::fake([
            '*test -f*docker-compose.yml*' => Process::result(output: 'standalone'),
            '*if \[ -f Dockerfile \]*' => Process::result(output: 'Dockerfile.production'),
            '*docker build -f Dockerfile.production*' => Process::result(
                output: 'Successfully built def456'
            ),
        ]);

        // Act
        $result = $this->service->buildContainer($project);

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_generates_dockerfile_when_missing(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createLaravelProject(['server_id' => $server->id]);

        Process::fake([
            '*test -f*docker-compose.yml*' => Process::result(output: 'standalone'),
            '*if \[ -f Dockerfile \]*' => Process::result(output: 'missing'),
            '*echo*Dockerfile*docker build*' => Process::result(
                output: 'Successfully built with generated Dockerfile'
            ),
        ]);

        // Act
        $result = $this->service->buildContainer($project);

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_handles_container_build_failure(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createProjectForServer($server);

        Process::fake([
            '*test -f*docker-compose.yml*' => Process::result(output: 'compose'),
            '*docker compose build*' => Process::result(
                output: '',
                errorOutput: 'Build failed: invalid syntax',
                exitCode: 1
            ),
        ]);

        // Act
        $result = $this->service->buildContainer($project);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('invalid syntax', $result['error']);
    }

    // ==========================================
    // CONTAINER START/STOP TESTS
    // ==========================================

    /** @test */
    public function it_starts_docker_compose_services(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createProjectForServer($server);

        Process::fake([
            '*test -f*docker-compose.yml*' => Process::result(output: 'compose'),
            '*docker compose down*' => Process::result(output: 'Stopped'),
            '*docker compose up -d*' => Process::result(
                output: 'Services started successfully'
            ),
        ]);

        // Act
        $result = $this->service->startContainer($project);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('started', $result['message']);
    }

    /** @test */
    public function it_starts_standalone_container(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createProjectForServer($server, ['port' => 8080]);

        Process::fake([
            '*test -f*docker-compose.yml*' => Process::result(output: 'standalone'),
            '*docker stop*' => Process::result(output: 'Stopped'),
            '*docker run*' => Process::result(
                output: 'abc123def456'
            ),
        ]);

        // Act
        $result = $this->service->startContainer($project);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('abc123def456', trim($result['container_id']));
        $this->assertEquals(8080, $result['port']);
    }

    /** @test */
    public function it_assigns_port_when_not_set(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createProjectForServer($server, ['port' => null]);

        Process::fake([
            '*test -f*docker-compose.yml*' => Process::result(output: 'standalone'),
            '*docker stop*' => Process::result(output: 'Stopped'),
            '*docker run*' => Process::result(
                output: 'container123'
            ),
        ]);

        // Act
        $result = $this->service->startContainer($project);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertNotNull($result['port']);
    }

    /** @test */
    public function it_stops_docker_compose_services(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createRunningProject(['server_id' => $server->id]);

        Process::fake([
            '*test -f*docker-compose.yml*' => Process::result(output: 'compose'),
            '*docker compose down*' => Process::result(
                output: 'Services stopped successfully'
            ),
        ]);

        // Act
        $result = $this->service->stopContainer($project);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('stopped', $result['message']);
    }

    /** @test */
    public function it_stops_standalone_container(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createRunningProject(['server_id' => $server->id]);

        Process::fake([
            '*test -f*docker-compose.yml*' => Process::result(output: 'standalone'),
            '*docker stop*' => Process::result(output: 'Container stopped'),
            '*docker rm*' => Process::result(output: 'Container removed'),
        ]);

        // Act
        $result = $this->service->stopContainer($project);

        // Assert
        $this->assertTrue($result['success']);
    }

    // ==========================================
    // CONTAINER LOGS TESTS
    // ==========================================

    /** @test */
    public function it_retrieves_docker_compose_logs(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createRunningProject(['server_id' => $server->id]);

        Process::fake([
            '*test -f*docker-compose.yml*' => Process::result(output: 'compose'),
            '*docker compose logs*' => Process::result(
                output: "app_1 | Log line 1\napp_1 | Log line 2"
            ),
        ]);

        // Act
        $result = $this->service->getContainerLogs($project, 100);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Log line 1', $result['logs']);
        $this->assertEquals('docker-compose', $result['source']);
    }

    /** @test */
    public function it_retrieves_standalone_container_logs(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createRunningProject(['server_id' => $server->id]);

        Process::fake([
            '*test -f*docker-compose.yml*' => Process::result(output: 'standalone'),
            '*docker logs --tail*' => Process::result(
                output: "Container log output\nAnother log line"
            ),
        ]);

        // Act
        $result = $this->service->getContainerLogs($project, 50);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Container log output', $result['logs']);
        $this->assertEquals('container', $result['source']);
    }

    /** @test */
    public function it_retrieves_laravel_logs_from_container(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createLaravelProject(['server_id' => $server->id]);

        Process::fake([
            '*docker exec*tail*laravel.log*' => Process::result(
                output: '[2024-01-01 00:00:00] local.ERROR: Test error'
            ),
        ]);

        // Act
        $result = $this->service->getLaravelLogs($project, 200);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Test error', $result['logs']);
        $this->assertEquals('container', $result['source']);
    }

    /** @test */
    public function it_falls_back_to_host_for_laravel_logs(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createLaravelProject(['server_id' => $server->id]);

        Process::fake([
            '*docker exec*tail*laravel.log*' => Process::result(
                output: 'Laravel log not found inside container'
            ),
            '*if \[ -f*laravel.log*' => Process::result(
                output: '[2024-01-01 00:00:00] production.ERROR: Host error'
            ),
        ]);

        // Act
        $result = $this->service->getLaravelLogs($project, 200);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Host error', $result['logs']);
        $this->assertEquals('host', $result['source']);
    }

    /** @test */
    public function it_clears_laravel_logs_successfully(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createLaravelProject(['server_id' => $server->id]);

        Process::fake([
            '*truncate*laravel.log*' => Process::result(output: 'cleared'),
        ]);

        // Act
        $result = $this->service->clearLaravelLogs($project);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('cleared successfully', $result['message']);
    }

    /** @test */
    public function it_creates_laravel_log_file_if_missing(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createLaravelProject(['server_id' => $server->id]);

        Process::fake([
            '*touch*laravel.log*' => Process::result(output: 'created'),
        ]);

        // Act
        $result = $this->service->clearLaravelLogs($project);

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_downloads_laravel_logs_successfully(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createLaravelProject(['server_id' => $server->id]);

        Process::fake([
            '*cat*laravel.log*' => Process::result(
                output: "[2024-01-01] local.ERROR: Full log content\n[2024-01-02] local.INFO: Info message"
            ),
        ]);

        // Act
        $result = $this->service->downloadLaravelLogs($project);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Full log content', $result['content']);
        $this->assertStringContainsString($project->slug, $result['filename']);
        $this->assertStringContainsString('.log', $result['filename']);
    }

    // ==========================================
    // CONTAINER STATUS & STATS TESTS
    // ==========================================

    /** @test */
    public function it_retrieves_container_status(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createRunningProject(['server_id' => $server->id]);

        Process::fake([
            '*docker ps -a --filter*' => Process::result(
                output: '{"ID":"abc123","Status":"Up 2 hours","State":"running"}'
            ),
        ]);

        // Act
        $result = $this->service->getContainerStatus($project);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertTrue($result['exists']);
        $this->assertEquals('abc123', $result['container']['ID']);
        $this->assertEquals('running', $result['container']['State']);
    }

    /** @test */
    public function it_detects_non_existent_container(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createProjectForServer($server);

        Process::fake([
            '*docker ps -a --filter*' => Process::result(output: ''),
        ]);

        // Act
        $result = $this->service->getContainerStatus($project);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertFalse($result['exists']);
        $this->assertNull($result['container']);
    }

    /** @test */
    public function it_retrieves_container_statistics(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createRunningProject(['server_id' => $server->id]);

        Process::fake([
            '*docker stats --no-stream*' => Process::result(
                output: '{"CPUPerc":"25.50%","MemPerc":"45.00%","NetIO":"1.2MB / 3.4MB"}'
            ),
        ]);

        // Act
        $result = $this->service->getContainerStats($project);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('25.50%', $result['stats']['CPUPerc']);
        $this->assertEquals('45.00%', $result['stats']['MemPerc']);
    }

    /** @test */
    public function it_retrieves_container_resource_limits(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createRunningProject(['server_id' => $server->id]);

        Process::fake([
            '*docker inspect --format*' => Process::result(
                output: '{"Memory":536870912,"CpuShares":1024,"CpuQuota":50000}'
            ),
        ]);

        // Act
        $result = $this->service->getContainerResourceLimits($project);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(536870912, $result['memory_limit']);
        $this->assertEquals(1024, $result['cpu_shares']);
        $this->assertEquals(50000, $result['cpu_quota']);
    }

    /** @test */
    public function it_sets_container_memory_limit(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createRunningProject(['server_id' => $server->id]);

        Process::fake([
            '*docker update --memory=512m*' => Process::result(
                output: 'Successfully updated memory limit'
            ),
        ]);

        // Act
        $result = $this->service->setContainerResourceLimits($project, 512);

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_sets_container_cpu_shares(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createRunningProject(['server_id' => $server->id]);

        Process::fake([
            '*docker update --cpu-shares=2048*' => Process::result(
                output: 'Successfully updated CPU shares'
            ),
        ]);

        // Act
        $result = $this->service->setContainerResourceLimits($project, null, 2048);

        // Assert
        $this->assertTrue($result['success']);
    }

    // ==========================================
    // VOLUME MANAGEMENT TESTS
    // ==========================================

    /** @test */
    public function it_lists_docker_volumes(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker volume ls*' => Process::result(
                output: '{"Name":"vol1","Driver":"local"}'."\n".'{"Name":"vol2","Driver":"local"}'
            ),
        ]);

        // Act
        $result = $this->service->listVolumes($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['volumes']);
        $this->assertEquals('vol1', $result['volumes'][0]['Name']);
    }

    /** @test */
    public function it_creates_docker_volume(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker volume create*' => Process::result(output: 'my-volume'),
        ]);

        // Act
        $result = $this->service->createVolume($server, 'my-volume');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('my-volume', $result['volume_name']);
    }

    /** @test */
    public function it_creates_volume_with_driver_and_labels(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker volume create --driver=nfs*' => Process::result(output: 'nfs-volume'),
        ]);

        // Act
        $result = $this->service->createVolume($server, 'nfs-volume', [
            'driver' => 'nfs',
            'labels' => ['project' => 'test'],
        ]);

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_deletes_docker_volume(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker volume rm*' => Process::result(output: 'old-volume'),
        ]);

        // Act
        $result = $this->service->deleteVolume($server, 'old-volume');

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_retrieves_volume_information(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker volume inspect*' => Process::result(
                output: '[{"Name":"test-vol","Driver":"local","Mountpoint":"/var/lib/docker/volumes/test-vol/_data"}]'
            ),
        ]);

        // Act
        $result = $this->service->getVolumeInfo($server, 'test-vol');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('test-vol', $result['volume']['Name']);
        $this->assertEquals('local', $result['volume']['Driver']);
    }

    // ==========================================
    // NETWORK MANAGEMENT TESTS
    // ==========================================

    /** @test */
    public function it_lists_docker_networks(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker network ls*' => Process::result(
                output: '{"Name":"bridge","Driver":"bridge"}'."\n".'{"Name":"host","Driver":"host"}'
            ),
        ]);

        // Act
        $result = $this->service->listNetworks($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['networks']);
        $this->assertEquals('bridge', $result['networks'][0]['Name']);
    }

    /** @test */
    public function it_creates_docker_network(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker network create*' => Process::result(output: 'abc123def456'),
        ]);

        // Act
        $result = $this->service->createNetwork($server, 'my-network', 'bridge');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('abc123def456', $result['network_id']);
    }

    /** @test */
    public function it_deletes_docker_network(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker network rm*' => Process::result(output: 'old-network'),
        ]);

        // Act
        $result = $this->service->deleteNetwork($server, 'old-network');

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_connects_container_to_network(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createRunningProject(['server_id' => $server->id]);

        Process::fake([
            '*docker network connect*' => Process::result(output: ''),
        ]);

        // Act
        $result = $this->service->connectContainerToNetwork($project, 'custom-network');

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_disconnects_container_from_network(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createRunningProject(['server_id' => $server->id]);

        Process::fake([
            '*docker network disconnect*' => Process::result(output: ''),
        ]);

        // Act
        $result = $this->service->disconnectContainerFromNetwork($project, 'custom-network');

        // Assert
        $this->assertTrue($result['success']);
    }

    // ==========================================
    // IMAGE MANAGEMENT TESTS
    // ==========================================

    /** @test */
    public function it_lists_docker_images(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker images*' => Process::result(
                output: '{"Repository":"nginx","Tag":"latest","Size":"150MB"}'."\n".
                        '{"Repository":"php","Tag":"8.2-fpm","Size":"450MB"}'
            ),
        ]);

        // Act
        $result = $this->service->listImages($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['images']);
        $this->assertEquals('nginx', $result['images'][0]['Repository']);
    }

    /** @test */
    public function it_lists_project_specific_images(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createProjectForServer($server, ['slug' => 'my-app']);

        Process::fake([
            '*docker images*' => Process::result(
                output: '{"Repository":"my-app","Tag":"latest","Size":"500MB"}'."\n".
                        '{"Repository":"nginx","Tag":"latest","Size":"150MB"}'
            ),
        ]);

        // Act
        $result = $this->service->listProjectImages($project);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['images']);
        $this->assertEquals('my-app', $result['images'][0]['Repository']);
    }

    /** @test */
    public function it_deletes_docker_image(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker rmi*' => Process::result(
                output: 'Deleted: sha256:abc123'
            ),
        ]);

        // Act
        $result = $this->service->deleteImage($server, 'old-image:tag');

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_prunes_unused_images(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker image prune -f*' => Process::result(
                output: 'Total reclaimed space: 1.2GB'
            ),
        ]);

        // Act
        $result = $this->service->pruneImages($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('1.2GB', $result['output']);
    }

    /** @test */
    public function it_prunes_all_unused_images(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker image prune -f -a*' => Process::result(
                output: 'Total reclaimed space: 5.5GB'
            ),
        ]);

        // Act
        $result = $this->service->pruneImages($server, true);

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_pulls_docker_image(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker pull*' => Process::result(
                output: 'latest: Pulling from library/nginx'
            ),
        ]);

        // Act
        $result = $this->service->pullImage($server, 'nginx:latest');

        // Assert
        $this->assertTrue($result['success']);
    }

    // ==========================================
    // DOCKER COMPOSE TESTS
    // ==========================================

    /** @test */
    public function it_checks_if_project_uses_docker_compose(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createProjectForServer($server);

        Process::fake([
            '*test -f*docker-compose.yml*' => Process::result(output: 'compose'),
        ]);

        // Act
        $result = $this->service->usesDockerCompose($project);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_checks_if_project_does_not_use_docker_compose(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createProjectForServer($server);

        Process::fake([
            '*test -f*docker-compose.yml*' => Process::result(output: 'standalone'),
        ]);

        // Act
        $result = $this->service->usesDockerCompose($project);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_deploys_with_docker_compose(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createProjectForServer($server);

        Process::fake([
            '*docker-compose up -d --build*' => Process::result(
                output: 'Creating network... Creating services...'
            ),
        ]);

        // Act
        $result = $this->service->deployWithCompose($project);

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_stops_compose_using_stop_compose_method(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createRunningProject(['server_id' => $server->id]);

        Process::fake([
            '*docker-compose down*' => Process::result(
                output: 'Stopping services... Removing containers...'
            ),
        ]);

        // Act
        $result = $this->service->stopCompose($project);

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_gets_docker_compose_service_status(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createRunningProject(['server_id' => $server->id]);

        Process::fake([
            '*docker-compose ps --format json*' => Process::result(
                output: '[{"Service":"app","Status":"Up 10 minutes"},{"Service":"db","Status":"Up 10 minutes"}]'
            ),
        ]);

        // Act
        $result = $this->service->getComposeStatus($project);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['services']);
    }

    /** @test */
    public function it_gets_app_container_name(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createProjectForServer($server, ['slug' => 'test-app']);

        Process::fake([
            '*docker ps --filter*test-app-app*' => Process::result(output: 'test-app-app'),
        ]);

        // Act
        $result = $this->service->getAppContainerName($project);

        // Assert
        $this->assertEquals('test-app-app', $result);
    }

    // ==========================================
    // CONTAINER EXECUTION TESTS
    // ==========================================

    /** @test */
    public function it_executes_command_in_container(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createRunningProject(['server_id' => $server->id]);

        Process::fake([
            '*docker exec*' => Process::result(
                output: 'Command executed successfully'
            ),
        ]);

        // Act
        $result = $this->service->execInContainer($project, 'php artisan cache:clear');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('successfully', $result['output']);
    }

    /** @test */
    public function it_handles_exec_command_failure(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createRunningProject(['server_id' => $server->id]);

        Process::fake([
            '*docker exec*' => Process::result(
                output: '',
                errorOutput: 'Command not found',
                exitCode: 126
            ),
        ]);

        // Act
        $result = $this->service->execInContainer($project, 'invalid-command');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['error']);
    }

    /** @test */
    public function it_gets_container_processes(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createRunningProject(['server_id' => $server->id]);

        Process::fake([
            '*docker top*' => Process::result(
                output: "UID PID PPID C STIME TTY TIME CMD\nroot 1 0 0 Jan01 ? 00:00:01 nginx"
            ),
        ]);

        // Act
        $result = $this->service->getContainerProcesses($project);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('nginx', $result['processes']);
    }

    // ==========================================
    // BACKUP & RESTORE TESTS
    // ==========================================

    /** @test */
    public function it_exports_container_as_backup(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createRunningProject(['server_id' => $server->id]);

        Process::fake([
            '*docker commit*' => Process::result(
                output: 'sha256:abc123def456'
            ),
        ]);

        // Act
        $result = $this->service->exportContainer($project);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('backup', $result['backup_name']);
        $this->assertNotEmpty($result['image_id']);
    }

    /** @test */
    public function it_saves_image_to_tar_file(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker save*' => Process::result(output: ''),
        ]);

        // Act
        $result = $this->service->saveImageToFile($server, 'my-image:latest', '/tmp/backup.tar');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('/tmp/backup.tar', $result['file_path']);
    }

    /** @test */
    public function it_loads_image_from_tar_file(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker load*' => Process::result(
                output: 'Loaded image: my-image:latest'
            ),
        ]);

        // Act
        $result = $this->service->loadImageFromFile($server, '/tmp/backup.tar');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Loaded image', $result['output']);
    }

    // ==========================================
    // DOCKER REGISTRY TESTS
    // ==========================================

    /** @test */
    public function it_logs_in_to_docker_registry(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker login*' => Process::result(
                output: 'Login Succeeded'
            ),
        ]);

        // Act
        $result = $this->service->registryLogin($server, 'docker.io', 'user', 'pass');

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_pushes_image_to_registry(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker push*' => Process::result(
                output: 'Pushing to registry...'
            ),
        ]);

        // Act
        $result = $this->service->pushImage($server, 'myrepo/myapp:latest');

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_tags_image_for_registry(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker tag*' => Process::result(output: ''),
        ]);

        // Act
        $result = $this->service->tagImage($server, 'myapp:latest', 'myrepo/myapp:v1.0');

        // Assert
        $this->assertTrue($result['success']);
    }

    // ==========================================
    // SYSTEM MANAGEMENT TESTS
    // ==========================================

    /** @test */
    public function it_gets_docker_system_info(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker info*' => Process::result(
                output: '{"ServerVersion":"24.0.5","ContainersRunning":5,"Images":20}'
            ),
        ]);

        // Act
        $result = $this->service->getSystemInfo($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('24.0.5', $result['info']['ServerVersion']);
        $this->assertEquals(5, $result['info']['ContainersRunning']);
    }

    /** @test */
    public function it_prunes_docker_system(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker system prune -f*' => Process::result(
                output: 'Total reclaimed space: 2.5GB'
            ),
        ]);

        // Act
        $result = $this->service->systemPrune($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('2.5GB', $result['output']);
    }

    /** @test */
    public function it_prunes_docker_system_with_volumes(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker system prune -f --volumes*' => Process::result(
                output: 'Total reclaimed space: 8.7GB'
            ),
        ]);

        // Act
        $result = $this->service->systemPrune($server, true);

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_gets_docker_disk_usage(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker system df*' => Process::result(
                output: '{"Type":"Images","Size":"5.2GB"}'."\n".'{"Type":"Containers","Size":"1.5GB"}'
            ),
        ]);

        // Act
        $result = $this->service->getDiskUsage($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['usage']);
        $this->assertEquals('Images', $result['usage'][0]['Type']);
    }

    // ==========================================
    // LOCALHOST DETECTION TESTS
    // ==========================================

    /** @test */
    public function it_detects_localhost_by_ip(): void
    {
        // Arrange
        $server = $this->createServerWithDocker(['ip_address' => '127.0.0.1']);

        Process::fake([
            '*docker --version*' => Process::result(output: 'Docker version 24.0.5'),
        ]);

        // Act
        $result = $this->service->checkDockerInstallation($server);

        // Assert
        $this->assertTrue($result['installed']);
    }

    /** @test */
    public function it_detects_localhost_ipv6(): void
    {
        // Arrange
        $server = $this->createServerWithDocker(['ip_address' => '::1']);

        Process::fake([
            '*docker --version*' => Process::result(output: 'Docker version 24.0.5'),
        ]);

        // Act
        $result = $this->service->checkDockerInstallation($server);

        // Assert
        $this->assertTrue($result['installed']);
    }

    // ==========================================
    // ENVIRONMENT VARIABLE TESTS
    // ==========================================

    /** @test */
    public function it_builds_environment_variables_for_container(): void
    {
        // This tests the protected method indirectly through startContainer
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createProjectForServer($server, [
            'environment' => 'production',
            'env_variables' => [
                'DB_HOST' => 'mysql',
                'REDIS_HOST' => 'redis',
            ],
        ]);

        Process::fake([
            '*test -f*docker-compose.yml*' => Process::result(output: 'standalone'),
            '*docker stop*' => Process::result(output: 'Stopped'),
            '*docker run*DB_HOST*REDIS_HOST*' => Process::result(output: 'container123'),
        ]);

        // Act
        $result = $this->service->startContainer($project);

        // Assert
        $this->assertTrue($result['success']);
    }

    // ==========================================
    // ERROR HANDLING TESTS
    // ==========================================

    /** @test */
    public function it_handles_ssh_connection_failure(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '192.168.1.100']);
        $this->mockSshFailure('Connection refused');

        // Act
        $result = $this->service->checkDockerInstallation($server);

        // Assert
        $this->assertFalse($result['installed']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_handles_timeout_during_docker_build(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createProjectForServer($server);

        Process::fake([
            '*test -f*docker-compose.yml*' => Process::result(output: 'compose'),
            '*docker compose build*' => Process::result(
                output: '',
                errorOutput: 'Operation timed out',
                exitCode: 124
            ),
        ]);

        // Act
        $result = $this->service->buildContainer($project);

        // Assert
        $this->assertFalse($result['success']);
    }

    /** @test */
    public function it_handles_container_not_running_for_exec(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $project = $this->createStoppedProject(['server_id' => $server->id]);

        Process::fake([
            '*docker exec*' => Process::result(
                output: '',
                errorOutput: 'Container is not running',
                exitCode: 125
            ),
        ]);

        // Act
        $result = $this->service->execInContainer($project, 'ls');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not running', $result['error']);
    }
}
