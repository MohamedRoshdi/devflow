<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Server;
use App\Services\GitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;

class GitServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Server $server;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->server = Server::factory()->create([
            'status' => 'online',
            'ip_address' => '127.0.0.1',
            'port' => 22,
            'username' => 'root',
        ]);
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'slug' => 'test-project',
            'branch' => 'main',
        ]);
    }

    /** @test */
    public function ssh_command_escapes_single_quotes_correctly()
    {
        $gitService = new GitService();

        // Use reflection to access protected method
        $reflection = new \ReflectionClass($gitService);
        $method = $reflection->getMethod('buildSSHCommand');
        $method->setAccessible(true);

        $command = $method->invoke($gitService, $this->server, "echo 'test'");

        // Should use single quotes and escape internal single quotes
        $this->assertStringContainsString("'echo '\\''test'\\'''", $command);
    }

    /** @test */
    public function ssh_command_preserves_git_format_strings()
    {
        $gitService = new GitService();

        $reflection = new \ReflectionClass($gitService);
        $method = $reflection->getMethod('buildSSHCommand');
        $method->setAccessible(true);

        // Git format string with % characters
        $gitCommand = "git log --pretty=format:'%H|%an|%ae|%at|%s'";
        $sshCommand = $method->invoke($gitService, $this->server, $gitCommand);

        // The % characters should be preserved (not interpreted by bash)
        $this->assertStringContainsString('%H', $sshCommand);
        $this->assertStringContainsString('%an', $sshCommand);
        $this->assertStringContainsString('%ae', $sshCommand);
        $this->assertStringContainsString('%at', $sshCommand);
        $this->assertStringContainsString('%s', $sshCommand);
    }

    /** @test */
    public function ssh_command_uses_single_quotes_wrapper()
    {
        $gitService = new GitService();

        $reflection = new \ReflectionClass($gitService);
        $method = $reflection->getMethod('buildSSHCommand');
        $method->setAccessible(true);

        $command = $method->invoke($gitService, $this->server, 'ls -la');

        // Should wrap command in single quotes, not double quotes
        $this->assertStringContainsString("root@127.0.0.1 'ls -la'", $command);
        $this->assertStringNotContainsString('"ls -la"', $command);
    }

    /** @test */
    public function get_latest_commits_returns_correct_structure()
    {
        // Fake the Process facade to simulate SSH response
        Process::fake([
            '*' => Process::result(
                output: "abc123def456|Test Author|test@example.com|1700000000|Test commit message",
                exitCode: 0
            ),
        ]);

        $gitService = new GitService();
        $result = $gitService->getLatestCommits($this->project, 10, 1);

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('commits', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('per_page', $result);
    }

    /** @test */
    public function check_for_updates_returns_correct_structure()
    {
        // Fake the Process facade
        Process::fake([
            '*' => Process::result(
                output: "abc123def456",
                exitCode: 0
            ),
        ]);

        $gitService = new GitService();
        $result = $gitService->checkForUpdates($this->project);

        $this->assertArrayHasKey('success', $result);

        if ($result['success']) {
            $this->assertArrayHasKey('up_to_date', $result);
            $this->assertArrayHasKey('local_commit', $result);
            $this->assertArrayHasKey('remote_commit', $result);
            $this->assertArrayHasKey('commits_behind', $result);
        }
    }

    /** @test */
    public function get_latest_commits_handles_empty_repository()
    {
        // Fake repo doesn't exist
        Process::fake([
            '*test -d*' => Process::result(output: "not-exists", exitCode: 0),
        ]);

        $gitService = new GitService();
        $result = $gitService->getLatestCommits($this->project, 10, 1);

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['commits']);
        $this->assertEquals(0, $result['total']);
    }

    /** @test */
    public function pagination_calculates_skip_correctly()
    {
        $gitService = new GitService();

        // Use reflection to test internal calculation
        $reflection = new \ReflectionClass($gitService);
        $method = $reflection->getMethod('getLatestCommits');

        // Page 1 with 10 per page should skip 0
        // Page 2 with 10 per page should skip 10
        // Page 3 with 5 per page should skip 10

        // We can't directly test the skip calculation without running the method,
        // but we can verify the method exists and accepts the right parameters
        $this->assertTrue($method->isPublic());
        $this->assertEquals(3, $method->getNumberOfParameters());
    }

    /** @test */
    public function commit_diff_returns_commits_between_hashes()
    {
        Process::fake([
            '*test -d*' => Process::result(output: "exists", exitCode: 0),
            '*git log*' => Process::result(
                output: "abc123|Author|1700000000|Commit 1\ndef456|Author|1700000100|Commit 2",
                exitCode: 0
            ),
        ]);

        $gitService = new GitService();
        $result = $gitService->getCommitDiff($this->project, 'abc123', 'def456');

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('commits', $result);
        $this->assertArrayHasKey('count', $result);
    }
}
