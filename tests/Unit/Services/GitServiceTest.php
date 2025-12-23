<?php

declare(strict_types=1);

namespace Tests\Unit\Services;


use PHPUnit\Framework\Attributes\Test;
use App\Models\Project;
use App\Models\Server;
use App\Services\GitService;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class GitServiceTest extends TestCase
{
    protected GitService $gitService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gitService = new GitService;
    }

    #[Test]
    public function it_can_get_latest_commits_from_repository(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id, 'branch' => 'main']);

        Process::fake([
            '*test -d*' => Process::result('exists'),
            '*safe.directory*' => Process::result(),
            '*fetch origin*' => Process::result(),
            '*rev-list --count origin/main*' => Process::result('25'),
            '*git log origin/main*' => Process::result(
                "abc123def456|John Doe|john@example.com|1704067200|feat: Add new feature\n".
                'def456ghi789|Jane Smith|jane@example.com|1704063600|fix: Fix bug in service'
            ),
        ]);

        $result = $this->gitService->getLatestCommits($project, perPage: 10, page: 1);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['commits']);
        $this->assertEquals(25, $result['total']);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(10, $result['per_page']);
        $this->assertEquals('abc123def456', $result['commits'][0]['hash']);
        $this->assertEquals('abc123d', $result['commits'][0]['short_hash']);
        $this->assertEquals('John Doe', $result['commits'][0]['author']);
        $this->assertEquals('john@example.com', $result['commits'][0]['email']);
    }

    #[Test]
    public function it_handles_pagination_for_commit_history(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*test -d*' => Process::result('exists'),
            '*safe.directory*' => Process::result(),
            '*fetch origin*' => Process::result(),
            '*rev-list --count*' => Process::result('50'),
            '*git log*' => Process::result(
                'commit1|Author1|author1@example.com|1704067200|Message 1'
            ),
        ]);

        $result = $this->gitService->getLatestCommits($project, perPage: 10, page: 2);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['page']);
        $this->assertEquals(10, $result['per_page']);
        $this->assertEquals(50, $result['total']);
    }

    #[Test]
    public function it_returns_empty_commits_when_repository_not_cloned(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*' => Process::result('not-exists'),
        ]);

        $result = $this->gitService->getLatestCommits($project);

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['commits']);
        $this->assertEquals(0, $result['total']);
    }

    #[Test]
    public function it_handles_fetch_failures_gracefully(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*test -d*' => Process::result('exists'),
            '*safe.directory*' => Process::result(),
            '*fetch*' => Process::result('fetch-failed', exitCode: 1),
            '*HEAD*' => Process::result('abc123|Author|author@example.com|1704067200|Commit message'),
            '*rev-list --count*' => Process::result('10'),
            '*git log*' => Process::result('abc123|Author|author@example.com|1704067200|Commit message'),
        ]);

        $result = $this->gitService->getLatestCommits($project);

        // Should still return commits from HEAD even if fetch fails
        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['commits']);
    }

    #[Test]
    public function it_handles_git_log_command_failures(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*test -d*' => Process::result('exists'),
            '*safe.directory*' => Process::result(),
            '*fetch origin*' => Process::result(),
            '*rev-list --count*' => Process::result('10'),
            '*git log*' => Process::result('', errorOutput: 'fatal: bad revision', exitCode: 1),
        ]);

        $result = $this->gitService->getLatestCommits($project);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Failed to get commit history', $result['error']);
    }

    #[Test]
    public function it_can_get_current_commit_information(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*test -d*' => Process::result('exists'),
            '*git log -1*' => Process::result('abc123def456|John Doe|1704067200|feat: Initial commit'),
        ]);

        $result = $this->gitService->getCurrentCommit($project);

        $this->assertNotNull($result);
        $this->assertEquals('abc123def456', $result['hash']);
        $this->assertEquals('abc123d', $result['short_hash']);
        $this->assertEquals('John Doe', $result['author']);
        $this->assertEquals(1704067200, $result['timestamp']);
        $this->assertEquals('feat: Initial commit', $result['message']);
        $this->assertArrayHasKey('date', $result);
    }

    #[Test]
    public function it_returns_null_when_repository_not_cloned_for_current_commit(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*test -d*' => Process::result('not-exists'),
        ]);

        $result = $this->gitService->getCurrentCommit($project);

        $this->assertNull($result);
    }

    #[Test]
    public function it_returns_null_when_git_log_fails_for_current_commit(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*test -d*' => Process::result('exists'),
            '*git log -1*' => Process::result('', errorOutput: 'fatal: error', exitCode: 1),
        ]);

        $result = $this->gitService->getCurrentCommit($project);

        $this->assertNull($result);
    }

    #[Test]
    public function it_can_check_for_updates(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id, 'branch' => 'main']);

        Process::fake([
            '*test -d*' => Process::result('exists'),
            '*safe.directory*' => Process::result(),
            '*fetch origin*' => Process::result(),
            '*git rev-parse HEAD*' => Process::result('abc123def456'),
            '*git rev-parse origin/main*' => Process::result('xyz789ghi012'),
            '*rev-list --count HEAD..origin/main*' => Process::result('3'),
            '*git show -s --format=*abc123def456*' => Process::result(
                'abc123def456|John Doe|1704067200|Local commit'
            ),
            '*git show -s --format=*xyz789ghi012*' => Process::result(
                'xyz789ghi012|Jane Smith|1704070800|Remote commit'
            ),
        ]);

        $result = $this->gitService->checkForUpdates($project);

        $this->assertTrue($result['success']);
        $this->assertFalse($result['up_to_date']);
        $this->assertEquals('abc123d', $result['local_commit']);
        $this->assertEquals('xyz789g', $result['remote_commit']);
        $this->assertEquals(3, $result['commits_behind']);
        $this->assertNotNull($result['local_meta']);
        $this->assertNotNull($result['remote_meta']);
        $this->assertEquals('John Doe', $result['local_meta']['author']);
        $this->assertEquals('Jane Smith', $result['remote_meta']['author']);
    }

    #[Test]
    public function it_detects_when_project_is_up_to_date(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $sameCommit = 'abc123def456';

        Process::fake([
            '*test -d*' => Process::result('exists'),
            '*safe.directory*' => Process::result(),
            '*fetch origin*' => Process::result(),
            '*git rev-parse HEAD*' => Process::result($sameCommit),
            '*git rev-parse origin/*' => Process::result($sameCommit),
            '*rev-list --count*' => Process::result('0'),
            '*git show -s --format=*' => Process::result(
                "{$sameCommit}|Author|1704067200|Commit message"
            ),
        ]);

        $result = $this->gitService->checkForUpdates($project);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['up_to_date']);
        $this->assertEquals(0, $result['commits_behind']);
    }

    #[Test]
    public function it_returns_default_values_when_repository_not_cloned_for_updates(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*' => Process::result('not-exists'),
        ]);

        $result = $this->gitService->checkForUpdates($project);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['up_to_date']);
        $this->assertNull($result['local_commit']);
        $this->assertNull($result['remote_commit']);
        $this->assertEquals(0, $result['commits_behind']);
    }

    #[Test]
    public function it_handles_fetch_failure_when_checking_for_updates(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*test -d*' => Process::result('exists'),
            '*safe.directory*' => Process::result(),
            '*fetch origin*' => Process::result('fetch-failed', exitCode: 1),
            '*git rev-parse HEAD*' => Process::result('abc123'),
            '*git rev-parse origin/*' => Process::result('abc123'),
            '*rev-list --count*' => Process::result('0'),
            '*git show -s*' => Process::result('abc123|Author|1704067200|Message'),
        ]);

        $result = $this->gitService->checkForUpdates($project);

        // Should still work with local commits
        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_handles_error_when_getting_local_commit_fails(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*test -d*' => Process::result('exists'),
            '*safe.directory*' => Process::result(),
            '*fetch origin*' => Process::result(),
            '*git rev-parse HEAD*' => Process::result('', errorOutput: 'fatal: error', exitCode: 1),
        ]);

        $result = $this->gitService->checkForUpdates($project);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Failed to get local commit', $result['error']);
    }

    #[Test]
    public function it_handles_error_when_getting_remote_commit_fails(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*test -d*' => Process::result('exists'),
            '*safe.directory*' => Process::result(),
            '*fetch origin*' => Process::result(),
            '*git rev-parse HEAD*' => Process::result('abc123'),
            '*git rev-parse origin/*' => Process::result('', errorOutput: 'fatal: error', exitCode: 1),
        ]);

        $result = $this->gitService->checkForUpdates($project);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Failed to get remote commit', $result['error']);
    }

    #[Test]
    public function it_can_update_project_commit_information(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'current_commit_hash' => null,
            'current_commit_message' => null,
            'last_commit_at' => null,
        ]);

        Process::fake([
            '*test -d*' => Process::result('exists'),
            '*git log -1*' => Process::result('abc123def456|John Doe|1704067200|feat: New feature'),
        ]);

        $result = $this->gitService->updateProjectCommitInfo($project);

        $this->assertTrue($result);
        $project->refresh();
        $this->assertEquals('abc123def456', $project->current_commit_hash);
        $this->assertEquals('feat: New feature', $project->current_commit_message);
        $this->assertNotNull($project->last_commit_at);
    }

    #[Test]
    public function it_returns_false_when_update_project_commit_info_fails(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*test -d*' => Process::result('not-exists'),
        ]);

        $result = $this->gitService->updateProjectCommitInfo($project);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_can_get_commit_diff_between_two_commits(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*test -d*' => Process::result('exists'),
            '*git log abc123..xyz789*' => Process::result(
                "commit1|Author1|1704067200|First commit\n".
                "commit2|Author2|1704070800|Second commit\n".
                'commit3|Author3|1704074400|Third commit'
            ),
        ]);

        $result = $this->gitService->getCommitDiff($project, 'abc123', 'xyz789');

        $this->assertTrue($result['success']);
        $this->assertCount(3, $result['commits']);
        $this->assertEquals(3, $result['count']);
        $this->assertEquals('commit1', $result['commits'][0]['hash']);
        $this->assertEquals('Author1', $result['commits'][0]['author']);
        $this->assertEquals('First commit', $result['commits'][0]['message']);
    }

    #[Test]
    public function it_handles_empty_commit_diff(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*test -d*' => Process::result('exists'),
            '*git log*' => Process::result(''),
        ]);

        $result = $this->gitService->getCommitDiff($project, 'abc123', 'abc123');

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['commits']);
        $this->assertEquals(0, $result['count']);
    }

    #[Test]
    public function it_returns_error_when_repository_not_cloned_for_diff(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*' => Process::result('not-exists'),
        ]);

        $result = $this->gitService->getCommitDiff($project, 'abc123', 'xyz789');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Repository not cloned yet', $result['error']);
    }

    #[Test]
    public function it_handles_git_log_failure_for_commit_diff(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*test -d*' => Process::result('exists'),
            '*git log*' => Process::result('', errorOutput: 'fatal: bad revision', exitCode: 1),
        ]);

        $result = $this->gitService->getCommitDiff($project, 'invalid', 'commits');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Failed to get commit diff', $result['error']);
    }

    #[Test]
    public function it_handles_exceptions_gracefully_in_get_latest_commits(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*test -d*' => Process::result('exists'),
            '*safe.directory*' => function () {
                throw new \Exception('Unexpected error');
            },
        ]);

        $result = $this->gitService->getLatestCommits($project);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Unexpected error', $result['error']);
    }

    #[Test]
    public function it_handles_exceptions_in_get_current_commit(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*test -d*' => function () {
                throw new \Exception('Process exception');
            },
        ]);

        $result = $this->gitService->getCurrentCommit($project);

        $this->assertNull($result);
    }

    #[Test]
    public function it_handles_exceptions_in_check_for_updates(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*test -d*' => function () {
                throw new \Exception('Critical error');
            },
        ]);

        $result = $this->gitService->checkForUpdates($project);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Critical error', $result['error']);
    }

    #[Test]
    public function it_handles_exceptions_in_get_commit_diff(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*test -d*' => function () {
                throw new \Exception('System failure');
            },
        ]);

        $result = $this->gitService->getCommitDiff($project, 'abc', 'xyz');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('System failure', $result['error']);
    }

    #[Test]
    public function it_builds_ssh_command_with_key_authentication(): void
    {
        $server = Server::factory()->withSshKey()->create([
            'username' => 'deploy',
            'ip_address' => '192.168.1.100',
            'port' => 2222,
        ]);

        $reflection = new \ReflectionClass($this->gitService);
        $method = $reflection->getMethod('buildSSHCommand');
        $method->setAccessible(true);

        $result = $method->invoke($this->gitService, $server, 'ls -la');

        $this->assertStringContainsString('ssh', $result);
        // Service quotes username and IP address for security
        $this->assertStringContainsString("'deploy'@'192.168.1.100'", $result);
        $this->assertStringContainsString('-p 2222', $result);
        $this->assertStringContainsString('StrictHostKeyChecking=no', $result);
        $this->assertStringContainsString('ls -la', $result);
    }

    #[Test]
    public function it_escapes_single_quotes_in_ssh_commands(): void
    {
        $server = Server::factory()->withSshKey()->create();

        $reflection = new \ReflectionClass($this->gitService);
        $method = $reflection->getMethod('buildSSHCommand');
        $method->setAccessible(true);

        $result = $method->invoke($this->gitService, $server, "echo 'test's value'");

        // Single quotes should be properly escaped
        $this->assertStringContainsString("\\'", $result);
    }

    #[Test]
    public function it_checks_repository_existence_via_ssh(): void
    {
        $server = Server::factory()->withSshKey()->create();

        Process::fake([
            '*test -d*' => Process::result('exists'),
        ]);

        $reflection = new \ReflectionClass($this->gitService);
        $method = $reflection->getMethod('isRepositoryCloned');
        $method->setAccessible(true);

        $result = $method->invoke($this->gitService, '/var/www/project', $server);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_detects_repository_not_cloned_via_ssh(): void
    {
        $server = Server::factory()->withSshKey()->create();

        Process::fake([
            '*' => Process::result('not-exists'),
        ]);

        $reflection = new \ReflectionClass($this->gitService);
        $method = $reflection->getMethod('isRepositoryCloned');
        $method->setAccessible(true);

        $result = $method->invoke($this->gitService, '/var/www/project', $server);

        $this->assertFalse($result);
    }

    // Note: Test 'it_always_uses_ssh_for_localhost' was removed - isLocalhost() method
    // no longer exists in GitService. The service now always uses SSH regardless of server IP.

    #[Test]
    public function it_configures_safe_directory_before_git_operations(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $safeDirectoryCalled = false;

        Process::fake([
            '*test -d*' => Process::result('exists'),
            '*safe.directory*' => function () use (&$safeDirectoryCalled) {
                $safeDirectoryCalled = true;

                return Process::result();
            },
            '*fetch origin*' => Process::result(),
            '*rev-list --count*' => Process::result('5'),
            '*git log*' => Process::result('abc|Author|email@test.com|1704067200|Message'),
        ]);

        $result = $this->gitService->getLatestCommits($project);

        $this->assertTrue($result['success']);
        $this->assertTrue($safeDirectoryCalled, 'Safe directory configuration should be called');
    }

    #[Test]
    public function it_uses_timeout_for_git_operations(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $timeoutFound = false;

        Process::fake([
            '*test -d*' => Process::result('exists'),
            '*safe.directory*' => Process::result(),
            '*timeout*fetch*' => function () use (&$timeoutFound) {
                $timeoutFound = true;

                return Process::result();
            },
            '*rev-list --count*' => Process::result('5'),
            '*git log*' => Process::result('abc|Author|email@test.com|1704067200|Message'),
        ]);

        $result = $this->gitService->getLatestCommits($project);

        $this->assertTrue($result['success']);
        $this->assertTrue($timeoutFound, 'Timeout should be used for git fetch');
    }

    #[Test]
    public function it_falls_back_to_head_when_origin_branch_not_available(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id, 'branch' => 'develop']);

        Process::fake([
            '*test -d*' => Process::result('exists'),
            '*safe.directory*' => Process::result(),
            '*fetch*' => Process::result(''),
            '*git log*' => Process::result('abc|Author|email@test.com|1704067200|Fallback commit'),
            '*rev-list --count origin/develop*' => Process::result('', exitCode: 128),
            '*rev-list*HEAD*' => Process::result('10'),
        ]);

        $result = $this->gitService->getLatestCommits($project);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['commits']);
        $this->assertEquals('Fallback commit', $result['commits'][0]['message']);
    }

    #[Test]
    public function it_handles_multiline_commit_messages(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*test -d*' => Process::result('exists'),
            '*git log -1*' => Process::result('abc123|Author|1704067200|feat: Add feature

This is a detailed commit message
with multiple lines of description'),
        ]);

        $result = $this->gitService->getCurrentCommit($project);

        $this->assertNotNull($result);
        $this->assertStringContainsString('feat: Add feature', $result['message']);
    }

    #[Test]
    public function it_formats_commit_dates_correctly(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $timestamp = 1704067200; // 2024-01-01 00:00:00 UTC

        Process::fake([
            '*test -d*' => Process::result('exists'),
            '*git log -1*' => Process::result("abc123|Author|{$timestamp}|Message"),
        ]);

        $result = $this->gitService->getCurrentCommit($project);

        $this->assertNotNull($result);
        $this->assertEquals($timestamp, $result['timestamp']);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $result['date']);
    }

    #[Test]
    public function it_creates_short_hash_from_full_commit_hash(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $fullHash = 'abc123def456ghi789jkl012mno345pqr678';

        Process::fake([
            '*test -d*' => Process::result('exists'),
            '*git log -1*' => Process::result("{$fullHash}|Author|1704067200|Message"),
        ]);

        $result = $this->gitService->getCurrentCommit($project);

        $this->assertNotNull($result);
        $this->assertEquals($fullHash, $result['hash']);
        $this->assertEquals('abc123d', $result['short_hash']);
        $this->assertEquals(7, strlen($result['short_hash']));
    }

    #[Test]
    public function it_handles_commit_messages_with_pipe_characters(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*test -d*' => Process::result('exists'),
            '*git log -1*' => Process::result('abc123|Author|1704067200|feat: Add feature | Fix bug | Update docs'),
        ]);

        $result = $this->gitService->getCurrentCommit($project);

        $this->assertNotNull($result);
        $this->assertEquals('feat: Add feature | Fix bug | Update docs', $result['message']);
    }

    #[Test]
    public function it_handles_metadata_parsing_failures_gracefully(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*test -d*' => Process::result('exists'),
            '*safe.directory*' => Process::result(),
            '*fetch origin*' => Process::result(),
            '*git rev-parse HEAD*' => Process::result('abc123'),
            '*git rev-parse origin/*' => Process::result('xyz789'),
            '*rev-list --count*' => Process::result('2'),
            '*git show -s --format=*abc123*' => Process::result('', exitCode: 1),
            '*git show -s --format=*xyz789*' => Process::result('', exitCode: 1),
        ]);

        $result = $this->gitService->checkForUpdates($project);

        $this->assertTrue($result['success']);
        $this->assertNull($result['local_meta']);
        $this->assertNull($result['remote_meta']);
    }

    #[Test]
    public function it_filters_empty_lines_from_commit_history(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*test -d*' => Process::result('exists'),
            '*safe.directory*' => Process::result(),
            '*fetch origin*' => Process::result(),
            '*rev-list --count*' => Process::result('2'),
            '*git log*' => Process::result(
                "abc123|Author1|email1@test.com|1704067200|Message 1\n\n".
                "def456|Author2|email2@test.com|1704070800|Message 2\n"
            ),
        ]);

        $result = $this->gitService->getLatestCommits($project);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['commits']);
    }

    #[Test]
    public function it_handles_different_branch_names(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $project = Project::factory()->create(['server_id' => $server->id, 'branch' => 'feature/new-feature']);

        Process::fake([
            '*test -d*' => Process::result('exists'),
            '*safe.directory*' => Process::result(),
            '*fetch*' => Process::result(),
            '*rev-list --count*' => Process::result('5'),
            '*git log*' => Process::result('abc|Author|email@test.com|1704067200|Feature commit'),
        ]);

        $result = $this->gitService->getLatestCommits($project);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['commits']);
        $this->assertEquals('Feature commit', $result['commits'][0]['message']);
    }
}
