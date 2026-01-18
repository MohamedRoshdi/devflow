<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\SecurityEvent;
use App\Models\Server;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Process;

class ServerSecurityService
{
    public function __construct(
        protected FirewallService $firewallService,
        protected Fail2banService $fail2banService,
        protected SSHSecurityService $sshSecurityService,
        protected SecurityScoreService $securityScoreService
    ) {}

    public function getSecurityOverview(Server $server): array
    {
        $ufwStatus = $this->firewallService->getUfwStatus($server);
        $fail2banStatus = $this->fail2banService->getFail2banStatus($server);
        $sshConfig = $this->sshSecurityService->getCurrentConfig($server);
        $openPorts = $this->getOpenPorts($server);
        $phpOptimization = $this->getPhpOptimizationStatus($server);

        return [
            'ufw' => $ufwStatus,
            'fail2ban' => $fail2banStatus,
            'ssh' => $sshConfig,
            'open_ports' => $openPorts,
            'php_optimization' => $phpOptimization,
            'security_score' => $server->security_score,
            'risk_level' => $server->security_risk_level,
            'last_scan_at' => $server->last_security_scan_at,
        ];
    }

    /**
     * Get PHP optimization status including OPcache and JIT settings
     *
     * @return array<string, mixed>
     */
    public function getPhpOptimizationStatus(Server $server): array
    {
        try {
            $phpVersion = $this->getPhpVersion($server);
            $opcacheStatus = $this->getOpcacheStatus($server);
            $jitStatus = $this->getJitStatus($server);
            $phpIniSettings = $this->getPhpIniSecuritySettings($server);

            return [
                'success' => true,
                'php_version' => $phpVersion,
                'opcache' => $opcacheStatus,
                'jit' => $jitStatus,
                'security_settings' => $phpIniSettings,
                'is_optimized' => $this->isPhpOptimized($opcacheStatus, $jitStatus, $phpIniSettings),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get installed PHP version
     */
    protected function getPhpVersion(Server $server): array
    {
        $result = $this->executeCommand($server, 'php -v 2>/dev/null | head -1');

        if (! $result['success'] || empty($result['output'])) {
            return ['installed' => false, 'version' => null];
        }

        preg_match('/PHP (\d+\.\d+\.\d+)/', $result['output'], $matches);

        return [
            'installed' => true,
            'version' => $matches[1] ?? 'unknown',
            'full_output' => $result['output'],
        ];
    }

    /**
     * Get OPcache status
     */
    protected function getOpcacheStatus(Server $server): array
    {
        $command = "php -r \"echo json_encode(['enabled' => function_exists('opcache_get_status') && opcache_get_status() !== false, 'config' => function_exists('opcache_get_configuration') ? opcache_get_configuration() : null]);\" 2>/dev/null";
        $result = $this->executeCommand($server, $command);

        if (! $result['success'] || empty($result['output'])) {
            $iniResult = $this->executeCommand($server, "php -i 2>/dev/null | grep -i 'opcache.enable'");

            return [
                'enabled' => str_contains(strtolower($iniResult['output'] ?? ''), 'on'),
                'memory_consumption' => null,
                'interned_strings_buffer' => null,
                'max_accelerated_files' => null,
                'validate_timestamps' => null,
                'jit_buffer_size' => null,
            ];
        }

        $data = json_decode($result['output'], true) ?? [];
        $config = $data['config']['directives'] ?? [];

        return [
            'enabled' => $data['enabled'] ?? false,
            'memory_consumption' => $config['opcache.memory_consumption'] ?? null,
            'interned_strings_buffer' => $config['opcache.interned_strings_buffer'] ?? null,
            'max_accelerated_files' => $config['opcache.max_accelerated_files'] ?? null,
            'validate_timestamps' => $config['opcache.validate_timestamps'] ?? null,
            'jit_buffer_size' => $config['opcache.jit_buffer_size'] ?? null,
        ];
    }

    /**
     * Get JIT status (PHP 8.0+)
     */
    protected function getJitStatus(Server $server): array
    {
        $command = "php -r \"if (function_exists('opcache_get_status')) { \\\$s = opcache_get_status(false); echo json_encode(\\\$s['jit'] ?? ['enabled' => false]); } else { echo json_encode(['enabled' => false]); }\" 2>/dev/null";
        $result = $this->executeCommand($server, $command);

        if (! $result['success'] || empty($result['output'])) {
            return ['enabled' => false, 'buffer_size' => null, 'buffer_free' => null];
        }

        $data = json_decode($result['output'], true) ?? [];

        return [
            'enabled' => $data['enabled'] ?? false,
            'on' => $data['on'] ?? false,
            'kind' => $data['kind'] ?? null,
            'opt_level' => $data['opt_level'] ?? null,
            'opt_flags' => $data['opt_flags'] ?? null,
            'buffer_size' => $data['buffer_size'] ?? null,
            'buffer_free' => $data['buffer_free'] ?? null,
        ];
    }

    /**
     * Get PHP.ini security-related settings
     */
    protected function getPhpIniSecuritySettings(Server $server): array
    {
        $settings = [
            'expose_php' => 'expose_php',
            'display_errors' => 'display_errors',
            'log_errors' => 'log_errors',
            'allow_url_fopen' => 'allow_url_fopen',
            'allow_url_include' => 'allow_url_include',
            'open_basedir' => 'open_basedir',
            'disable_functions' => 'disable_functions',
            'session.cookie_httponly' => 'session.cookie_httponly',
            'session.cookie_secure' => 'session.cookie_secure',
            'session.use_strict_mode' => 'session.use_strict_mode',
        ];

        $result = [];
        foreach ($settings as $key => $ini) {
            $command = "php -r \"echo ini_get('{$ini}');\" 2>/dev/null";
            $cmdResult = $this->executeCommand($server, $command);
            $result[$key] = $cmdResult['output'] ?? '';
        }

        return $result;
    }

    /**
     * Check if PHP is properly optimized for production
     */
    protected function isPhpOptimized(array $opcache, array $jit, array $security): bool
    {
        $score = 0;
        $maxScore = 5;

        if ($opcache['enabled'] ?? false) {
            $score++;
        }

        if (($opcache['memory_consumption'] ?? 0) >= 128) {
            $score++;
        }

        if ($jit['enabled'] ?? false) {
            $score++;
        }

        if (($security['expose_php'] ?? '1') === '' || strtolower($security['expose_php'] ?? 'On') === 'off') {
            $score++;
        }

        if (($security['display_errors'] ?? '1') === '' || strtolower($security['display_errors'] ?? 'On') === 'off') {
            $score++;
        }

        return $score >= 3;
    }

    /**
     * Apply production PHP optimizations to server
     */
    public function applyPhpProductionOptimizations(Server $server): array
    {
        try {
            $sudoPrefix = $this->getSudoPrefix($server);
            $phpVersion = $this->getPhpVersion($server);

            if (! ($phpVersion['installed'] ?? false)) {
                return ['success' => false, 'message' => 'PHP is not installed on this server'];
            }

            $version = $phpVersion['version'] ?? '8.4';
            $majorMinor = implode('.', array_slice(explode('.', $version), 0, 2));

            $optimizations = [
                'opcache.enable=1',
                'opcache.memory_consumption=256',
                'opcache.interned_strings_buffer=64',
                'opcache.max_accelerated_files=50000',
                'opcache.validate_timestamps=0',
                'opcache.revalidate_freq=0',
                'opcache.save_comments=1',
                'opcache.enable_cli=0',
                'opcache.jit=1255',
                'opcache.jit_buffer_size=128M',
                'expose_php=Off',
                'display_errors=Off',
                'log_errors=On',
                'error_log=/var/log/php-errors.log',
            ];

            $iniContent = "\n; DevFlow Pro Production Optimizations\n" . implode("\n", $optimizations);
            $iniFile = "/etc/php/{$majorMinor}/fpm/conf.d/99-devflow-optimizations.ini";

            $command = "{$sudoPrefix}bash -c 'echo \"{$iniContent}\" > {$iniFile}'";
            $result = $this->executeCommand($server, $command);

            if (! $result['success']) {
                return ['success' => false, 'message' => 'Failed to write optimization file: ' . $result['error']];
            }

            $restartResult = $this->executeCommand($server, "{$sudoPrefix}systemctl restart php{$majorMinor}-fpm");

            $this->logSecurityEvent(
                $server,
                'php_optimization_applied',
                "Applied production PHP optimizations for PHP {$majorMinor}"
            );

            return [
                'success' => true,
                'message' => "PHP {$majorMinor} production optimizations applied successfully",
                'optimizations' => $optimizations,
                'fpm_restarted' => $restartResult['success'],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to apply optimizations: ' . $e->getMessage()];
        }
    }

    /**
     * Full security audit combining all checks
     */
    public function runSecurityAudit(Server $server): array
    {
        $overview = $this->getSecurityOverview($server);

        $issues = [];
        $recommendations = [];

        if (! ($overview['ufw']['enabled'] ?? false)) {
            $issues[] = 'UFW firewall is not enabled';
            $recommendations[] = 'Enable UFW firewall to protect server ports';
        }

        if (! ($overview['fail2ban']['enabled'] ?? false)) {
            $issues[] = 'Fail2ban is not running';
            $recommendations[] = 'Enable Fail2ban to prevent brute-force attacks';
        }

        if (! ($overview['php_optimization']['is_optimized'] ?? false)) {
            $issues[] = 'PHP is not optimized for production';
            $recommendations[] = 'Apply PHP production optimizations (OPcache + JIT)';
        }

        $phpSecurity = $overview['php_optimization']['security_settings'] ?? [];
        if (strtolower($phpSecurity['expose_php'] ?? 'On') !== 'off') {
            $issues[] = 'PHP version is exposed in headers';
            $recommendations[] = 'Set expose_php=Off in php.ini';
        }

        if (strtolower($phpSecurity['display_errors'] ?? 'On') !== 'off') {
            $issues[] = 'PHP errors are displayed to users';
            $recommendations[] = 'Set display_errors=Off in production';
        }

        $server->update([
            'last_security_scan_at' => now(),
        ]);

        return [
            'overview' => $overview,
            'issues_count' => count($issues),
            'issues' => $issues,
            'recommendations' => $recommendations,
            'is_secure' => count($issues) === 0,
            'scanned_at' => now()->toIso8601String(),
        ];
    }

    public function checkSecurityToolsStatus(Server $server): array
    {
        $ufwStatus = $this->firewallService->getUfwStatus($server);
        $fail2banStatus = $this->fail2banService->getFail2banStatus($server);

        $server->update([
            'ufw_installed' => $ufwStatus['installed'] ?? false,
            'ufw_enabled' => $ufwStatus['enabled'] ?? false,
            'fail2ban_installed' => $fail2banStatus['installed'] ?? false,
            'fail2ban_enabled' => $fail2banStatus['enabled'] ?? false,
        ]);

        return [
            'ufw' => $ufwStatus,
            'fail2ban' => $fail2banStatus,
        ];
    }

    public function getOpenPorts(Server $server): array
    {
        try {
            $command = "sudo ss -tulpn 2>/dev/null | grep LISTEN | awk '{print \$5}' | sed 's/.*://' | sort -u";
            $result = $this->executeCommand($server, $command);

            if (! $result['success']) {
                return ['success' => false, 'ports' => []];
            }

            $ports = array_filter(
                array_map('trim', explode("\n", $result['output'])),
                fn ($port) => is_numeric($port)
            );

            return [
                'success' => true,
                'ports' => array_values($ports),
                'count' => count($ports),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'ports' => [], 'error' => $e->getMessage()];
        }
    }

    public function logSecurityEvent(
        Server $server,
        string $eventType,
        ?string $details = null,
        ?string $sourceIp = null,
        ?array $metadata = null
    ): SecurityEvent {
        return SecurityEvent::create([
            'server_id' => $server->id,
            'event_type' => $eventType,
            'details' => $details,
            'source_ip' => $sourceIp,
            'metadata' => $metadata,
            'user_id' => Auth::id(),
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\SecurityEvent>
     */
    public function getRecentEvents(Server $server, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $server->securityEvents()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    protected function executeCommand(Server $server, string $command): array
    {
        try {
            $isLocalhost = $this->isLocalhost($server->ip_address);

            if ($isLocalhost) {
                $result = Process::timeout(30)->run($command);

                return [
                    'success' => $result->successful(),
                    'output' => trim($result->output()),
                    'error' => $result->errorOutput(),
                ];
            }

            $sshCommand = $this->buildSSHCommand($server, $command);
            $result = Process::timeout(30)->run($sshCommand);

            return [
                'success' => $result->successful(),
                'output' => trim($result->output()),
                'error' => $result->errorOutput(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'output' => '',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function isLocalhost(string $ip): bool
    {
        $localIPs = ['127.0.0.1', '::1', 'localhost'];

        if (in_array($ip, $localIPs)) {
            return true;
        }

        $serverIP = gethostbyname(gethostname());
        if ($ip === $serverIP) {
            return true;
        }

        return false;
    }

    protected function buildSSHCommand(Server $server, string $remoteCommand): string
    {
        $port = $server->port ?? 22;

        $sshOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-o ConnectTimeout=10',
            '-o LogLevel=ERROR',
            '-p '.$port,
        ];

        // Escape double quotes and backslashes for the remote command
        $escapedCommand = str_replace(['\\', '"', '$', '`'], ['\\\\', '\\"', '\\$', '\\`'], $remoteCommand);

        if ($server->ssh_password) {
            $escapedPassword = escapeshellarg($server->ssh_password);

            return sprintf(
                'sshpass -p %s ssh %s %s@%s "%s" 2>&1',
                $escapedPassword,
                implode(' ', $sshOptions),
                $server->username,
                $server->ip_address,
                $escapedCommand
            );
        }

        $sshOptions[] = '-o BatchMode=yes';

        if ($server->ssh_key) {
            $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
            file_put_contents($keyFile, $server->ssh_key);
            chmod($keyFile, 0600);
            $sshOptions[] = '-i '.$keyFile;
        }

        return sprintf(
            'ssh %s %s@%s "%s" 2>&1',
            implode(' ', $sshOptions),
            $server->username,
            $server->ip_address,
            $escapedCommand
        );
    }

    protected function getSudoPrefix(Server $server): string
    {
        $isRoot = strtolower($server->username) === 'root';

        if ($isRoot) {
            return '';
        }

        if ($server->ssh_password) {
            $escapedPassword = str_replace("'", "'\\''", $server->ssh_password);

            return "echo '{$escapedPassword}' | sudo -S ";
        }

        return 'sudo ';
    }
}
