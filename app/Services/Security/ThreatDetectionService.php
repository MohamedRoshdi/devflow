<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Events\SecurityIncidentDetected;
use App\Models\SecurityIncident;
use App\Models\Server;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class ThreatDetectionService
{
    /**
     * Known malicious process names to detect
     *
     * @var array<int, string>
     */
    protected array $suspiciousProcesses = [
        'httpd6',
        'sshd2',
        'minerd',
        'xmrig',
        'kdevtmpfsi',
        'kinsing',
        'solrd',
        'dbused',
        'masscan',
        'zmap',
        'hydra',
        'medusa',
        'ncrack',
    ];

    /**
     * Patterns that indicate malware directory names
     *
     * @var array<int, string>
     */
    protected array $suspiciousDirectoryPatterns = [
        '/^\.\d{16,}$/',      // .1234567890123456 (random numbers)
        '/^\.\.\./',           // ... (dot dot dot)
        '/^\. $/',             // ". " (dot space)
        '/^\.\.\ /',           // ".. " (dot dot space)
        '/^\.vbus$/',          // Known malware dir
        '/^\.cache[0-9]+$/',   // .cache followed by numbers
    ];

    /**
     * Run a comprehensive threat scan on a server
     *
     * @return array{threats: array<int, array<string, mixed>>, scan_time: float, server_id: int}
     */
    public function scanServer(Server $server): array
    {
        $startTime = microtime(true);
        $threats = [];

        Log::info('Starting threat scan', ['server_id' => $server->id, 'server_name' => $server->name]);

        // Run all detection checks
        $checks = [
            'backdoor_users' => $this->detectBackdoorUsers($server),
            'hidden_directories' => $this->detectHiddenDirectories($server),
            'suspicious_processes' => $this->detectSuspiciousProcesses($server),
            'outbound_ssh' => $this->detectOutboundSSH($server),
            'unauthorized_ssh_keys' => $this->detectUnauthorizedSSHKeys($server),
            'malicious_cron' => $this->detectMaliciousCronJobs($server),
            'world_writable' => $this->detectWorldWritableFiles($server),
        ];

        foreach ($checks as $checkType => $result) {
            if (! empty($result['threats'])) {
                $threats = array_merge($threats, $result['threats']);
            }
        }

        // Update server last scan time
        $server->update(['last_threat_scan_at' => now()]);

        $scanTime = round(microtime(true) - $startTime, 2);

        Log::info('Threat scan completed', [
            'server_id' => $server->id,
            'threats_found' => count($threats),
            'scan_time' => $scanTime,
        ]);

        return [
            'threats' => $threats,
            'scan_time' => $scanTime,
            'server_id' => $server->id,
        ];
    }

    /**
     * Create incidents from detected threats
     *
     * @param array<int, array<string, mixed>> $threats
     * @return array<int, SecurityIncident>
     */
    public function createIncidentsFromThreats(Server $server, array $threats, ?int $userId = null): array
    {
        $incidents = [];

        foreach ($threats as $threat) {
            // Check if similar incident already exists and is active
            $existingIncident = SecurityIncident::where('server_id', $server->id)
                ->where('incident_type', $threat['type'])
                ->active()
                ->first();

            if ($existingIncident) {
                // Update existing incident with new findings
                $existingFindings = $existingIncident->findings ?? [];
                $existingFindings[] = $threat;
                $existingIncident->update(['findings' => $existingFindings]);
                $incidents[] = $existingIncident;

                continue;
            }

            // Create new incident
            $incident = SecurityIncident::create([
                'server_id' => $server->id,
                'user_id' => $userId,
                'incident_type' => $threat['type'],
                'severity' => $threat['severity'],
                'status' => SecurityIncident::STATUS_DETECTED,
                'title' => $threat['title'],
                'description' => $threat['description'],
                'findings' => [$threat],
                'affected_items' => $threat['affected_items'] ?? [],
                'detected_at' => now(),
            ]);

            // Increment active incidents count
            $server->increment('active_incidents_count');

            // Fire event for notifications
            event(new SecurityIncidentDetected($incident));

            $incidents[] = $incident;
        }

        return $incidents;
    }

    /**
     * Detect users with UID 0 (root-level backdoors)
     *
     * @return array{success: bool, threats: array<int, array<string, mixed>>}
     */
    public function detectBackdoorUsers(Server $server): array
    {
        $threats = [];

        $result = $this->executeCommand($server, "cat /etc/passwd | grep ':0:' | grep -v '^root:'");

        if ($result['success'] && ! empty($result['output'])) {
            $lines = array_filter(explode("\n", $result['output']));

            foreach ($lines as $line) {
                $parts = explode(':', $line);
                $username = $parts[0] ?? 'unknown';

                $threats[] = [
                    'type' => SecurityIncident::TYPE_BACKDOOR_USER,
                    'severity' => SecurityIncident::SEVERITY_CRITICAL,
                    'title' => "Backdoor user detected: {$username}",
                    'description' => "User '{$username}' has UID 0 (root privileges). This is a critical security threat indicating a potential backdoor.",
                    'affected_items' => [
                        'username' => $username,
                        'passwd_entry' => $line,
                    ],
                ];
            }
        }

        return ['success' => true, 'threats' => $threats];
    }

    /**
     * Detect hidden directories with suspicious names
     *
     * @return array{success: bool, threats: array<int, array<string, mixed>>}
     */
    public function detectHiddenDirectories(Server $server): array
    {
        $threats = [];
        $searchPaths = ['/home', '/tmp', '/var/tmp', '/dev/shm'];

        foreach ($searchPaths as $path) {
            $result = $this->executeCommand(
                $server,
                "find {$path} -maxdepth 3 -type d -name '.*' 2>/dev/null | head -50"
            );

            if (! $result['success'] || empty($result['output'])) {
                continue;
            }

            $directories = array_filter(explode("\n", $result['output']));

            foreach ($directories as $dir) {
                $dirName = basename($dir);

                // Skip common legitimate hidden directories
                if (in_array($dirName, ['.', '..', '.ssh', '.cache', '.config', '.local', '.npm', '.composer', '.git', '.docker', '.gnupg'], true)) {
                    continue;
                }

                // Check against suspicious patterns
                foreach ($this->suspiciousDirectoryPatterns as $pattern) {
                    if (preg_match($pattern, $dirName)) {
                        // Check if directory contains executables or scripts
                        $contentsResult = $this->executeCommand($server, "ls -la '{$dir}' 2>/dev/null | head -10");

                        $threats[] = [
                            'type' => SecurityIncident::TYPE_MALWARE,
                            'severity' => SecurityIncident::SEVERITY_HIGH,
                            'title' => "Suspicious hidden directory: {$dir}",
                            'description' => "Hidden directory with suspicious name pattern detected. This may contain malware or attack tools.",
                            'affected_items' => [
                                'path' => $dir,
                                'name' => $dirName,
                                'contents' => $contentsResult['output'] ?? 'Unable to list',
                            ],
                        ];

                        break;
                    }
                }

                // Also check for ... directory specifically
                if ($dirName === '...' || str_starts_with($dirName, '...')) {
                    $threats[] = [
                        'type' => SecurityIncident::TYPE_MALWARE,
                        'severity' => SecurityIncident::SEVERITY_HIGH,
                        'title' => "Hidden directory with evasion name: {$dir}",
                        'description' => "Directory named '...' is a common technique to hide malware. Legitimate directories never use this name.",
                        'affected_items' => [
                            'path' => $dir,
                            'name' => $dirName,
                        ],
                    ];
                }
            }
        }

        return ['success' => true, 'threats' => $threats];
    }

    /**
     * Detect suspicious processes (fake kernel threads, known malware)
     *
     * @return array{success: bool, threats: array<int, array<string, mixed>>}
     */
    public function detectSuspiciousProcesses(Server $server): array
    {
        $threats = [];

        // Check for processes with deleted executables (common for malware)
        $result = $this->executeCommand(
            $server,
            "ls -la /proc/*/exe 2>/dev/null | grep '(deleted)' | head -20"
        );

        if ($result['success'] && ! empty($result['output'])) {
            $lines = array_filter(explode("\n", $result['output']));

            foreach ($lines as $line) {
                // Extract PID and path
                if (preg_match('/\/proc\/(\d+)\/exe -> (.+) \(deleted\)/', $line, $matches)) {
                    $pid = $matches[1];
                    $exePath = $matches[2];

                    // Get process details
                    $processResult = $this->executeCommand($server, "ps aux | grep '^ *[^ ]* *{$pid} ' | head -1");
                    $cmdlineResult = $this->executeCommand($server, "cat /proc/{$pid}/cmdline 2>/dev/null | tr '\\0' ' '");

                    $threats[] = [
                        'type' => SecurityIncident::TYPE_SUSPICIOUS_PROCESS,
                        'severity' => SecurityIncident::SEVERITY_CRITICAL,
                        'title' => "Process running from deleted executable: PID {$pid}",
                        'description' => "A process is running from a deleted file. This is a strong indicator of malware that was removed but is still running in memory.",
                        'affected_items' => [
                            'pid' => $pid,
                            'deleted_path' => $exePath,
                            'process_info' => $processResult['output'] ?? 'Unknown',
                            'cmdline' => $cmdlineResult['output'] ?? 'Unknown',
                        ],
                    ];
                }
            }
        }

        // Check for known malicious process names
        $processListResult = $this->executeCommand($server, 'ps aux');

        if ($processListResult['success'] && ! empty($processListResult['output'])) {
            foreach ($this->suspiciousProcesses as $processName) {
                if (stripos($processListResult['output'], $processName) !== false) {
                    $threats[] = [
                        'type' => SecurityIncident::TYPE_SUSPICIOUS_PROCESS,
                        'severity' => SecurityIncident::SEVERITY_CRITICAL,
                        'title' => "Known malicious process detected: {$processName}",
                        'description' => "Process '{$processName}' is associated with known malware or attack tools.",
                        'affected_items' => [
                            'process_name' => $processName,
                        ],
                    ];
                }
            }

            // Check for fake kernel threads (processes in brackets that aren't kernel threads)
            if (preg_match_all('/\[([^\]]+)\]/', $processListResult['output'], $matches)) {
                foreach ($matches[1] as $threadName) {
                    // Legitimate kernel threads we should ignore
                    $legitimateThreads = ['kthreadd', 'kswapd0', 'migration', 'ksoftirqd', 'watchdog', 'cpuhp', 'netns', 'rcu'];

                    $isLegitimate = false;
                    foreach ($legitimateThreads as $legit) {
                        if (str_starts_with($threadName, $legit)) {
                            $isLegitimate = true;

                            break;
                        }
                    }

                    // kswapd1, kswapd2 etc. are suspicious (there's usually only kswapd0)
                    if (preg_match('/^kswapd[1-9]/', $threadName)) {
                        $threats[] = [
                            'type' => SecurityIncident::TYPE_SUSPICIOUS_PROCESS,
                            'severity' => SecurityIncident::SEVERITY_CRITICAL,
                            'title' => "Fake kernel thread detected: [{$threadName}]",
                            'description' => "Process disguised as kernel thread '{$threadName}'. Legitimate systems usually only have kswapd0.",
                            'affected_items' => [
                                'thread_name' => $threadName,
                            ],
                        ];
                    }
                }
            }
        }

        return ['success' => true, 'threats' => $threats];
    }

    /**
     * Detect outbound SSH connections (potential attacks originating from server)
     *
     * @return array{success: bool, threats: array<int, array<string, mixed>>}
     */
    public function detectOutboundSSH(Server $server): array
    {
        $threats = [];

        // Check for outbound connections to port 22
        $result = $this->executeCommand(
            $server,
            "netstat -tn 2>/dev/null | grep ':22 ' | grep -v ':{$server->port}' | grep ESTABLISHED"
        );

        if ($result['success'] && ! empty($result['output'])) {
            $connections = array_filter(explode("\n", $result['output']));
            $externalConnections = [];

            foreach ($connections as $conn) {
                // Parse the connection line to get destination IP
                if (preg_match('/(\d+\.\d+\.\d+\.\d+):22/', $conn, $matches)) {
                    $destIp = $matches[1];

                    // Exclude localhost
                    if (! in_array($destIp, ['127.0.0.1', $server->ip_address], true)) {
                        $externalConnections[] = $destIp;
                    }
                }
            }

            if (! empty($externalConnections)) {
                $threats[] = [
                    'type' => SecurityIncident::TYPE_OUTBOUND_ATTACK,
                    'severity' => SecurityIncident::SEVERITY_HIGH,
                    'title' => 'Outbound SSH connections detected',
                    'description' => 'This server has active outbound SSH connections to external hosts. This may indicate SSH brute-force attacks originating from your server.',
                    'affected_items' => [
                        'destination_ips' => array_unique($externalConnections),
                        'connection_count' => count($externalConnections),
                    ],
                ];
            }
        }

        return ['success' => true, 'threats' => $threats];
    }

    /**
     * Detect unauthorized SSH keys
     *
     * @return array{success: bool, threats: array<int, array<string, mixed>>}
     */
    public function detectUnauthorizedSSHKeys(Server $server): array
    {
        $threats = [];

        // Get all authorized_keys files
        $result = $this->executeCommand(
            $server,
            "find /home /root -name 'authorized_keys' 2>/dev/null"
        );

        if (! $result['success'] || empty($result['output'])) {
            return ['success' => true, 'threats' => $threats];
        }

        $keyFiles = array_filter(explode("\n", $result['output']));

        foreach ($keyFiles as $keyFile) {
            // Get the last modification time
            $modResult = $this->executeCommand($server, "stat -c '%Y %n' '{$keyFile}' 2>/dev/null");

            if ($modResult['success'] && ! empty($modResult['output'])) {
                // Check if file was modified in the last 7 days (could be suspicious)
                $parts = explode(' ', $modResult['output'], 2);
                $modTime = (int) ($parts[0] ?? 0);
                $currentTime = time();

                // If modified within last 24 hours and we're not aware of it
                if ($modTime > $currentTime - 86400) {
                    $keyContent = $this->executeCommand($server, "cat '{$keyFile}' 2>/dev/null");

                    $threats[] = [
                        'type' => SecurityIncident::TYPE_UNAUTHORIZED_SSH_KEY,
                        'severity' => SecurityIncident::SEVERITY_MEDIUM,
                        'title' => "Recently modified authorized_keys: {$keyFile}",
                        'description' => 'SSH authorized_keys file was modified recently. Verify all keys are legitimate.',
                        'affected_items' => [
                            'file_path' => $keyFile,
                            'modified_at' => date('Y-m-d H:i:s', $modTime),
                            'key_count' => substr_count($keyContent['output'] ?? '', 'ssh-'),
                        ],
                    ];
                }
            }
        }

        return ['success' => true, 'threats' => $threats];
    }

    /**
     * Detect malicious cron jobs
     *
     * @return array{success: bool, threats: array<int, array<string, mixed>>}
     */
    public function detectMaliciousCronJobs(Server $server): array
    {
        $threats = [];
        $suspiciousPatterns = [
            '/wget/',
            '/curl.*\|.*sh/',
            '/curl.*\|.*bash/',
            '/base64.*-d/',
            '/python.*-c/',
            '/perl.*-e/',
            '/nc\s+-/',
            '/\/dev\/tcp/',
            '/\.onion/',
        ];

        // Check system crontabs
        $cronDirs = ['/etc/cron.d', '/var/spool/cron/crontabs'];

        foreach ($cronDirs as $cronDir) {
            $result = $this->executeCommand($server, "cat {$cronDir}/* 2>/dev/null");

            if ($result['success'] && ! empty($result['output'])) {
                foreach ($suspiciousPatterns as $pattern) {
                    if (preg_match($pattern, $result['output'], $matches)) {
                        $threats[] = [
                            'type' => SecurityIncident::TYPE_MALICIOUS_CRON,
                            'severity' => SecurityIncident::SEVERITY_HIGH,
                            'title' => 'Suspicious cron job pattern detected',
                            'description' => 'A cron job contains a pattern commonly used by malware for downloading and executing scripts.',
                            'affected_items' => [
                                'pattern' => $pattern,
                                'location' => $cronDir,
                            ],
                        ];

                        break;
                    }
                }
            }
        }

        // Check user crontabs
        $result = $this->executeCommand($server, 'crontab -l 2>/dev/null');

        if ($result['success'] && ! empty($result['output'])) {
            foreach ($suspiciousPatterns as $pattern) {
                if (preg_match($pattern, $result['output'])) {
                    $threats[] = [
                        'type' => SecurityIncident::TYPE_MALICIOUS_CRON,
                        'severity' => SecurityIncident::SEVERITY_HIGH,
                        'title' => 'Suspicious user cron job detected',
                        'description' => 'User crontab contains suspicious patterns.',
                        'affected_items' => [
                            'pattern' => $pattern,
                            'crontab' => $result['output'],
                        ],
                    ];

                    break;
                }
            }
        }

        return ['success' => true, 'threats' => $threats];
    }

    /**
     * Detect world-writable files in sensitive locations
     *
     * @return array{success: bool, threats: array<int, array<string, mixed>>}
     */
    public function detectWorldWritableFiles(Server $server): array
    {
        $threats = [];
        $sensitivePaths = ['/etc', '/usr/bin', '/usr/sbin', '/bin', '/sbin'];

        foreach ($sensitivePaths as $path) {
            $result = $this->executeCommand(
                $server,
                "find {$path} -type f -perm -002 2>/dev/null | head -10"
            );

            if ($result['success'] && ! empty($result['output'])) {
                $files = array_filter(explode("\n", $result['output']));

                if (! empty($files)) {
                    $threats[] = [
                        'type' => SecurityIncident::TYPE_FILE_INTEGRITY,
                        'severity' => SecurityIncident::SEVERITY_MEDIUM,
                        'title' => "World-writable files in {$path}",
                        'description' => 'Sensitive system files are world-writable, which could allow any user to modify them.',
                        'affected_items' => [
                            'path' => $path,
                            'files' => $files,
                        ],
                    ];
                }
            }
        }

        return ['success' => true, 'threats' => $threats];
    }

    /**
     * Execute a command on the server (local or remote)
     *
     * @return array{success: bool, output: string, error: string}
     */
    protected function executeCommand(Server $server, string $command): array
    {
        try {
            $isLocalhost = $this->isLocalhost($server->ip_address) || $server->is_current_server;

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

    /**
     * Build SSH command for remote execution
     */
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

    /**
     * Check if the IP address is localhost
     */
    protected function isLocalhost(string $ipAddress): bool
    {
        $localhostAddresses = ['127.0.0.1', '::1', 'localhost'];

        if (in_array($ipAddress, $localhostAddresses, true)) {
            return true;
        }

        $serverIps = gethostbynamel(gethostname()) ?: [];

        return in_array($ipAddress, $serverIps, true);
    }
}
