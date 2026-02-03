<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Events\SecurityIncidentDetected;
use App\Models\KnownThreatSignature;
use App\Models\SecurityIncident;
use App\Models\Server;
use App\Traits\ExecutesServerCommands;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class ThreatDetectionService
{
    use ExecutesServerCommands;
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
     * Run a guardian-enhanced threat scan with additional detection methods
     *
     * @return array{threats: array<int, array<string, mixed>>, scan_time: float, server_id: int}
     */
    public function scanServerGuardian(Server $server): array
    {
        $startTime = microtime(true);
        $threats = [];

        Log::info('Starting guardian threat scan', ['server_id' => $server->id]);

        // Run standard checks
        $standardResult = $this->scanServer($server);
        $threats = $standardResult['threats'];

        // Run guardian-specific checks
        $guardianChecks = [
            'crypto_miners' => $this->detectCryptoMiners($server),
            'irc_botnets' => $this->detectIRCBotnets($server),
            'malicious_systemd' => $this->detectMaliciousSystemdServices($server),
            'disguised_processes' => $this->detectDisguisedProcesses($server),
            'mining_pools' => $this->detectMiningPoolConnections($server),
            'proxy_tunnels' => $this->detectProxyTunnels($server),
            'persistence' => $this->detectPersistenceMechanisms($server),
        ];

        foreach ($guardianChecks as $result) {
            if (! empty($result['threats'])) {
                $threats = array_merge($threats, $result['threats']);
            }
        }

        $server->update(['last_guardian_scan_at' => now()]);

        $scanTime = round(microtime(true) - $startTime, 2);

        Log::info('Guardian scan completed', [
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
     * Detect crypto miners (XMRig, c3pool, mining processes)
     *
     * @return array{success: bool, threats: array<int, array<string, mixed>>}
     */
    public function detectCryptoMiners(Server $server): array
    {
        $threats = [];
        $minerPatterns = ['xmrig', 'minerd', 'c3pool', 'javasw', 'kdevtmpfsi', 'kinsing', 'xmr-stak', 'cpuminer'];

        // Check running processes for miner names
        $result = $this->executeCommand($server, 'ps aux');

        if ($result['success'] && ! empty($result['output'])) {
            foreach ($minerPatterns as $pattern) {
                if (stripos($result['output'], $pattern) !== false) {
                    // Get the specific process line
                    $processResult = $this->executeCommand($server, "ps aux | grep -i '{$pattern}' | grep -v grep");

                    $threats[] = [
                        'type' => SecurityIncident::TYPE_CRYPTO_MINER,
                        'severity' => SecurityIncident::SEVERITY_CRITICAL,
                        'title' => "Crypto miner process detected: {$pattern}",
                        'description' => "Process matching crypto miner pattern '{$pattern}' is running. This consumes server resources and indicates a compromise.",
                        'affected_items' => [
                            'pattern' => $pattern,
                            'process_info' => $processResult['output'] ?? 'Unknown',
                        ],
                    ];
                }
            }

            // Check for high CPU processes that could be unnamed miners
            $highCpuResult = $this->executeCommand($server, "ps aux --sort=-%cpu | head -5 | awk '{if(\$3 > 80) print \$0}'");
            if ($highCpuResult['success'] && ! empty($highCpuResult['output'])) {
                $lines = array_filter(explode("\n", $highCpuResult['output']));
                foreach ($lines as $line) {
                    if (preg_match('/(\S+)\s+(\d+)\s+(\d+\.\d+)/', $line, $matches)) {
                        $pid = $matches[2];
                        $cpu = (float) $matches[3];

                        // Verify it's not a known legitimate process
                        $exeResult = $this->executeCommand($server, "readlink -f /proc/{$pid}/exe 2>/dev/null");
                        $exePath = trim($exeResult['output'] ?? '');

                        $legitimatePaths = ['/usr/bin/', '/usr/sbin/', '/usr/lib/', '/sbin/', '/bin/'];
                        $isLegitimate = false;
                        foreach ($legitimatePaths as $legit) {
                            if (str_starts_with($exePath, $legit)) {
                                $isLegitimate = true;

                                break;
                            }
                        }

                        if (! $isLegitimate && $cpu > 80) {
                            $threats[] = [
                                'type' => SecurityIncident::TYPE_CRYPTO_MINER,
                                'severity' => SecurityIncident::SEVERITY_HIGH,
                                'title' => "Suspicious high-CPU process: PID {$pid} ({$cpu}%)",
                                'description' => "Process with PID {$pid} is consuming {$cpu}% CPU from non-standard path. Could be an unnamed miner.",
                                'affected_items' => [
                                    'pid' => $pid,
                                    'cpu_usage' => $cpu,
                                    'exe_path' => $exePath,
                                    'process_line' => $line,
                                ],
                            ];
                        }
                    }
                }
            }
        }

        return ['success' => true, 'threats' => $threats];
    }

    /**
     * Detect IRC botnets (port 6667, Perl masquerading)
     *
     * @return array{success: bool, threats: array<int, array<string, mixed>>}
     */
    public function detectIRCBotnets(Server $server): array
    {
        $threats = [];

        // Check for connections to IRC port 6667
        $result = $this->executeCommand(
            $server,
            "ss -tnp 2>/dev/null | grep ':6667' || netstat -tnp 2>/dev/null | grep ':6667'"
        );

        if ($result['success'] && ! empty($result['output'])) {
            $threats[] = [
                'type' => SecurityIncident::TYPE_IRC_BOTNET,
                'severity' => SecurityIncident::SEVERITY_CRITICAL,
                'title' => 'IRC botnet connection detected (port 6667)',
                'description' => 'Active connection to IRC port 6667 detected. This is a strong indicator of botnet C&C communication.',
                'affected_items' => [
                    'connections' => $result['output'],
                ],
            ];
        }

        // Check for Perl processes masquerading as system processes
        $perlResult = $this->executeCommand(
            $server,
            "ps aux | grep perl | grep -v grep"
        );

        if ($perlResult['success'] && ! empty($perlResult['output'])) {
            $lines = array_filter(explode("\n", $perlResult['output']));
            $suspiciousNames = ['acpid', 'sshd', 'cron', 'rsyslogd', 'atd', 'kswapd'];

            foreach ($lines as $line) {
                foreach ($suspiciousNames as $name) {
                    if (stripos($line, $name) !== false && stripos($line, 'perl') !== false) {
                        $threats[] = [
                            'type' => SecurityIncident::TYPE_IRC_BOTNET,
                            'severity' => SecurityIncident::SEVERITY_CRITICAL,
                            'title' => "Perl process masquerading as {$name}",
                            'description' => "A Perl process is disguised as '{$name}'. This is a common IRC botnet technique.",
                            'affected_items' => [
                                'process_line' => $line,
                                'disguised_as' => $name,
                            ],
                        ];
                    }
                }
            }
        }

        return ['success' => true, 'threats' => $threats];
    }

    /**
     * Detect malicious systemd services running from suspicious locations
     *
     * @return array{success: bool, threats: array<int, array<string, mixed>>}
     */
    public function detectMaliciousSystemdServices(Server $server): array
    {
        $threats = [];
        $suspiciousPaths = ['/tmp', '/var/tmp', '/dev/shm', '/root/.'];
        $safeServiceWhitelist = config('devflow.guardian.safe_services', [
            'ssh', 'sshd', 'docker', 'nginx', 'apache2', 'mysql', 'mariadb',
            'postgresql', 'redis-server', 'fail2ban', 'ufw', 'supervisor',
            'cron', 'rsyslog', 'systemd-', 'networkd', 'resolved', 'timesyncd',
            'snapd', 'containerd', 'php', 'node',
        ]);

        // List all active services with their ExecStart paths
        $result = $this->executeCommand(
            $server,
            "systemctl list-units --type=service --all --no-pager --plain 2>/dev/null | awk '{print \$1}' | grep '.service'"
        );

        if (! $result['success'] || empty($result['output'])) {
            return ['success' => true, 'threats' => $threats];
        }

        $services = array_filter(explode("\n", $result['output']));

        foreach ($services as $service) {
            $service = trim($service);
            if (empty($service)) {
                continue;
            }

            // Skip whitelisted services
            $isSafe = false;
            foreach ($safeServiceWhitelist as $safe) {
                if (str_contains($service, $safe)) {
                    $isSafe = true;

                    break;
                }
            }
            if ($isSafe) {
                continue;
            }

            // Get ExecStart path for this service
            $execResult = $this->executeCommand(
                $server,
                "systemctl show {$service} --property=ExecStart --no-pager 2>/dev/null"
            );

            if (! $execResult['success'] || empty($execResult['output'])) {
                continue;
            }

            $execPath = $execResult['output'];

            // Check if ExecStart contains suspicious paths
            foreach ($suspiciousPaths as $suspiciousPath) {
                if (str_contains($execPath, $suspiciousPath)) {
                    $threats[] = [
                        'type' => SecurityIncident::TYPE_MALICIOUS_SERVICE,
                        'severity' => SecurityIncident::SEVERITY_CRITICAL,
                        'title' => "Malicious systemd service: {$service}",
                        'description' => "Service '{$service}' executes from suspicious path '{$suspiciousPath}'. Legitimate services don't run from temporary directories.",
                        'affected_items' => [
                            'service_name' => $service,
                            'exec_start' => $execPath,
                            'suspicious_path' => $suspiciousPath,
                        ],
                    ];

                    break;
                }
            }
        }

        return ['success' => true, 'threats' => $threats];
    }

    /**
     * Detect processes whose binary doesn't match their displayed name
     *
     * @return array{success: bool, threats: array<int, array<string, mixed>>}
     */
    public function detectDisguisedProcesses(Server $server): array
    {
        $threats = [];

        // Get all processes with their PIDs
        $result = $this->executeCommand(
            $server,
            "ps -eo pid,comm --no-headers 2>/dev/null | head -100"
        );

        if (! $result['success'] || empty($result['output'])) {
            return ['success' => true, 'threats' => $threats];
        }

        $processes = array_filter(explode("\n", $result['output']));

        foreach ($processes as $proc) {
            $proc = trim($proc);
            if (empty($proc)) {
                continue;
            }

            if (! preg_match('/^\s*(\d+)\s+(.+)$/', $proc, $matches)) {
                continue;
            }

            $pid = $matches[1];
            $comm = trim($matches[2]);

            // Skip kernel threads (shown in brackets)
            if (str_starts_with($comm, '[')) {
                continue;
            }

            // Skip low PIDs (system processes)
            if ((int) $pid < 100) {
                continue;
            }

            // Read the actual binary path
            $exeResult = $this->executeCommand($server, "readlink -f /proc/{$pid}/exe 2>/dev/null");
            $exePath = trim($exeResult['output'] ?? '');

            if (empty($exePath) || str_contains($exePath, '(deleted)')) {
                continue;
            }

            $exeBasename = basename($exePath);

            // If process name doesn't match binary name, it could be disguised
            if ($exeBasename !== $comm && ! str_contains($exeBasename, $comm) && ! str_contains($comm, $exeBasename)) {
                // Special case: interpreters (perl, python, ruby) running scripts
                $interpreters = ['perl', 'python', 'python3', 'ruby', 'node', 'bash', 'sh', 'dash', 'php'];
                if (in_array($exeBasename, $interpreters, true)) {
                    // This is normal - interpreter running a script named differently
                    continue;
                }

                // Check if it's a well-known binary
                $knownBinaries = ['/usr/bin/', '/usr/sbin/', '/bin/', '/sbin/', '/usr/lib/'];
                $isKnown = false;
                foreach ($knownBinaries as $path) {
                    if (str_starts_with($exePath, $path)) {
                        $isKnown = true;

                        break;
                    }
                }

                if (! $isKnown) {
                    $threats[] = [
                        'type' => SecurityIncident::TYPE_PROCESS_DISGUISE,
                        'severity' => SecurityIncident::SEVERITY_HIGH,
                        'title' => "Disguised process: '{$comm}' is actually '{$exeBasename}'",
                        'description' => "Process '{$comm}' (PID {$pid}) is running from '{$exePath}' which doesn't match its displayed name. This suggests process name spoofing.",
                        'affected_items' => [
                            'pid' => $pid,
                            'displayed_name' => $comm,
                            'actual_binary' => $exePath,
                            'actual_basename' => $exeBasename,
                        ],
                    ];
                }
            }
        }

        return ['success' => true, 'threats' => $threats];
    }

    /**
     * Detect connections to known mining pools (stratum ports)
     *
     * @return array{success: bool, threats: array<int, array<string, mixed>>}
     */
    public function detectMiningPoolConnections(Server $server): array
    {
        $threats = [];
        $stratumPorts = [3333, 5555, 7777, 8888, 9999, 14433, 14444, 45560];

        foreach ($stratumPorts as $port) {
            $result = $this->executeCommand(
                $server,
                "ss -tnp 2>/dev/null | grep ':{$port} ' | grep ESTAB || netstat -tnp 2>/dev/null | grep ':{$port} ' | grep ESTABLISHED"
            );

            if ($result['success'] && ! empty($result['output'])) {
                $threats[] = [
                    'type' => SecurityIncident::TYPE_MINING_POOL_CONNECTION,
                    'severity' => SecurityIncident::SEVERITY_CRITICAL,
                    'title' => "Mining pool connection detected on port {$port}",
                    'description' => "Active connection to stratum mining port {$port}. This indicates the server is actively mining cryptocurrency.",
                    'affected_items' => [
                        'port' => $port,
                        'connections' => $result['output'],
                    ],
                ];
            }
        }

        // Also check for known mining pool domains in DNS
        $dnsResult = $this->executeCommand(
            $server,
            "ss -tnp 2>/dev/null | grep ESTAB | awk '{print \$5}' | cut -d: -f1 | sort -u | head -20"
        );

        if ($dnsResult['success'] && ! empty($dnsResult['output'])) {
            $miningDomains = ['c3pool', 'minergate', 'nanopool', 'supportxmr', 'hashvault', 'moneroocean'];
            $ips = array_filter(explode("\n", $dnsResult['output']));

            foreach ($ips as $ip) {
                $ip = trim($ip);
                if (empty($ip)) {
                    continue;
                }

                $hostResult = $this->executeCommand($server, "host {$ip} 2>/dev/null | head -1");
                if ($hostResult['success'] && ! empty($hostResult['output'])) {
                    foreach ($miningDomains as $domain) {
                        if (stripos($hostResult['output'], $domain) !== false) {
                            $threats[] = [
                                'type' => SecurityIncident::TYPE_MINING_POOL_CONNECTION,
                                'severity' => SecurityIncident::SEVERITY_CRITICAL,
                                'title' => "Connection to mining pool: {$domain}",
                                'description' => "Server is connected to known mining pool domain containing '{$domain}'.",
                                'affected_items' => [
                                    'ip' => $ip,
                                    'domain_match' => $domain,
                                    'dns_result' => $hostResult['output'],
                                ],
                            ];
                        }
                    }
                }
            }
        }

        return ['success' => true, 'threats' => $threats];
    }

    /**
     * Detect proxy tunnel services (v2ray, shadowsocks)
     *
     * @return array{success: bool, threats: array<int, array<string, mixed>>}
     */
    public function detectProxyTunnels(Server $server): array
    {
        $threats = [];
        $proxyServices = ['v2ray', 'xray', 'shadowsocks', 'ss-server', 'ss-local', 'trojan', 'hysteria'];

        // Check running processes
        $result = $this->executeCommand($server, 'ps aux');

        if ($result['success'] && ! empty($result['output'])) {
            foreach ($proxyServices as $service) {
                if (stripos($result['output'], $service) !== false) {
                    $processResult = $this->executeCommand($server, "ps aux | grep -i '{$service}' | grep -v grep");

                    $threats[] = [
                        'type' => SecurityIncident::TYPE_PROXY_TUNNEL,
                        'severity' => SecurityIncident::SEVERITY_HIGH,
                        'title' => "Proxy tunnel service detected: {$service}",
                        'description' => "Proxy/tunnel service '{$service}' is running. Unless intentionally installed, this indicates the server is being used as a proxy relay.",
                        'affected_items' => [
                            'service' => $service,
                            'process_info' => $processResult['output'] ?? 'Unknown',
                        ],
                    ];
                }
            }
        }

        // Check for systemd services
        foreach ($proxyServices as $service) {
            $svcResult = $this->executeCommand($server, "systemctl is-active {$service} 2>/dev/null");
            if ($svcResult['success'] && trim($svcResult['output']) === 'active') {
                $threats[] = [
                    'type' => SecurityIncident::TYPE_PROXY_TUNNEL,
                    'severity' => SecurityIncident::SEVERITY_HIGH,
                    'title' => "Proxy tunnel systemd service active: {$service}",
                    'description' => "Systemd service '{$service}' is active. This is an unauthorized proxy/tunnel.",
                    'affected_items' => [
                        'service_name' => $service,
                        'status' => 'active',
                    ],
                ];
            }
        }

        return ['success' => true, 'threats' => $threats];
    }

    /**
     * Detect persistence mechanisms (crontabs, bashrc, rc.local)
     *
     * @return array{success: bool, threats: array<int, array<string, mixed>>}
     */
    public function detectPersistenceMechanisms(Server $server): array
    {
        $threats = [];
        $suspiciousPatterns = [
            '/wget\s+.*\|\s*(sh|bash)/',
            '/curl\s+.*\|\s*(sh|bash)/',
            '/base64\s.*-d/',
            '/\/dev\/tcp\//',
            '/nc\s+-[elp]/',
            '/python[23]?\s+-c\s/',
            '/nohup\s.*&/',
        ];

        // Check ALL user crontabs (not just root)
        $usersResult = $this->executeCommand($server, "cut -d: -f1 /etc/passwd");
        if ($usersResult['success'] && ! empty($usersResult['output'])) {
            $users = array_filter(explode("\n", $usersResult['output']));

            foreach ($users as $user) {
                $user = trim($user);
                if (empty($user)) {
                    continue;
                }

                $cronResult = $this->executeCommand($server, "crontab -u {$user} -l 2>/dev/null");
                if ($cronResult['success'] && ! empty($cronResult['output']) && ! str_contains($cronResult['output'], 'no crontab for')) {
                    foreach ($suspiciousPatterns as $pattern) {
                        if (preg_match($pattern, $cronResult['output'])) {
                            $threats[] = [
                                'type' => SecurityIncident::TYPE_PERSISTENCE,
                                'severity' => SecurityIncident::SEVERITY_HIGH,
                                'title' => "Suspicious crontab for user: {$user}",
                                'description' => "User '{$user}' has a crontab entry matching suspicious pattern. This may be a persistence mechanism.",
                                'affected_items' => [
                                    'user' => $user,
                                    'pattern' => $pattern,
                                    'crontab' => $cronResult['output'],
                                ],
                            ];

                            break;
                        }
                    }
                }
            }
        }

        // Check .bashrc and .profile for suspicious entries
        $homeResult = $this->executeCommand($server, "ls -d /home/*/ /root/ 2>/dev/null");
        if ($homeResult['success'] && ! empty($homeResult['output'])) {
            $homes = array_filter(explode("\n", $homeResult['output']));

            foreach ($homes as $home) {
                $home = rtrim(trim($home), '/');
                if (empty($home)) {
                    continue;
                }

                foreach (['.bashrc', '.profile', '.bash_profile'] as $rcFile) {
                    $rcResult = $this->executeCommand($server, "cat '{$home}/{$rcFile}' 2>/dev/null");
                    if ($rcResult['success'] && ! empty($rcResult['output'])) {
                        foreach ($suspiciousPatterns as $pattern) {
                            if (preg_match($pattern, $rcResult['output'])) {
                                $threats[] = [
                                    'type' => SecurityIncident::TYPE_PERSISTENCE,
                                    'severity' => SecurityIncident::SEVERITY_HIGH,
                                    'title' => "Suspicious entry in {$home}/{$rcFile}",
                                    'description' => "File '{$home}/{$rcFile}' contains suspicious commands that match malware persistence patterns.",
                                    'affected_items' => [
                                        'file' => "{$home}/{$rcFile}",
                                        'pattern' => $pattern,
                                    ],
                                ];

                                break;
                            }
                        }
                    }
                }
            }
        }

        // Check /etc/rc.local
        $rcLocalResult = $this->executeCommand($server, "cat /etc/rc.local 2>/dev/null");
        if ($rcLocalResult['success'] && ! empty($rcLocalResult['output'])) {
            foreach ($suspiciousPatterns as $pattern) {
                if (preg_match($pattern, $rcLocalResult['output'])) {
                    $threats[] = [
                        'type' => SecurityIncident::TYPE_PERSISTENCE,
                        'severity' => SecurityIncident::SEVERITY_HIGH,
                        'title' => 'Suspicious entry in /etc/rc.local',
                        'description' => '/etc/rc.local contains suspicious commands that will run on boot.',
                        'affected_items' => [
                            'file' => '/etc/rc.local',
                            'pattern' => $pattern,
                            'content' => $rcLocalResult['output'],
                        ],
                    ];

                    break;
                }
            }
        }

        return ['success' => true, 'threats' => $threats];
    }
}
