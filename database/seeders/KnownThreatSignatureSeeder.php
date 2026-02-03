<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\KnownThreatSignature;
use Illuminate\Database\Seeder;

class KnownThreatSignatureSeeder extends Seeder
{
    public function run(): void
    {
        $signatures = [
            // Crypto Miners
            [
                'name' => 'XMRig Miner',
                'category' => 'crypto_miner',
                'signature_type' => 'process_name',
                'pattern' => 'xmrig',
                'severity' => 'critical',
                'description' => 'XMRig is the most common Monero cryptocurrency miner found on compromised servers.',
                'remediation_hint' => 'Kill the process, remove the binary, and check for persistence mechanisms (crontab, systemd services).',
            ],
            [
                'name' => 'Minerd Miner',
                'category' => 'crypto_miner',
                'signature_type' => 'process_name',
                'pattern' => 'minerd',
                'severity' => 'critical',
                'description' => 'Minerd is a CPU mining tool often deployed by attackers.',
                'remediation_hint' => 'Kill the process and remove the binary from /tmp or hidden directories.',
            ],
            [
                'name' => 'C3Pool Connection',
                'category' => 'crypto_miner',
                'signature_type' => 'network_pattern',
                'pattern' => 'c3pool',
                'severity' => 'critical',
                'description' => 'C3Pool is a popular mining pool used by attackers for Monero mining.',
                'remediation_hint' => 'Block connections to c3pool domains and kill mining processes.',
            ],
            [
                'name' => 'Javasw Miner',
                'category' => 'crypto_miner',
                'signature_type' => 'process_name',
                'pattern' => 'javasw',
                'severity' => 'critical',
                'description' => 'Malware disguised as Java process, actually a crypto miner.',
                'remediation_hint' => 'Verify binary path - legitimate Java should be in /usr/bin or /usr/lib.',
            ],
            [
                'name' => 'Kdevtmpfsi Miner',
                'category' => 'crypto_miner',
                'signature_type' => 'process_name',
                'pattern' => 'kdevtmpfsi',
                'severity' => 'critical',
                'description' => 'Well-known crypto miner malware that persists via crontab and hides in /tmp.',
                'remediation_hint' => 'Remove binary, clean crontab, also look for companion process kinsing.',
            ],
            [
                'name' => 'Kinsing Backdoor',
                'category' => 'crypto_miner',
                'signature_type' => 'process_name',
                'pattern' => 'kinsing',
                'severity' => 'critical',
                'description' => 'Kinsing is a backdoor that downloads and manages crypto miners.',
                'remediation_hint' => 'Kill process, remove binary, check Docker containers for exploitation.',
            ],
            [
                'name' => 'Stratum Port 3333',
                'category' => 'crypto_miner',
                'signature_type' => 'port_connection',
                'pattern' => '3333',
                'severity' => 'critical',
                'description' => 'Connection to stratum mining port 3333.',
                'remediation_hint' => 'Block outbound port 3333 via UFW.',
            ],
            [
                'name' => 'Stratum Port 5555',
                'category' => 'crypto_miner',
                'signature_type' => 'port_connection',
                'pattern' => '5555',
                'severity' => 'critical',
                'description' => 'Connection to stratum mining port 5555.',
                'remediation_hint' => 'Block outbound port 5555 via UFW.',
            ],
            [
                'name' => 'Stratum Port 7777',
                'category' => 'crypto_miner',
                'signature_type' => 'port_connection',
                'pattern' => '7777',
                'severity' => 'critical',
                'description' => 'Connection to stratum mining port 7777.',
                'remediation_hint' => 'Block outbound port 7777 via UFW.',
            ],

            // IRC Botnets
            [
                'name' => 'IRC Port 6667',
                'category' => 'botnet',
                'signature_type' => 'port_connection',
                'pattern' => '6667',
                'severity' => 'critical',
                'description' => 'Connection to IRC port 6667, commonly used for botnet C&C communication.',
                'remediation_hint' => 'Block outbound port 6667 and identify the connecting process.',
            ],
            [
                'name' => 'Fake kswapd1',
                'category' => 'botnet',
                'signature_type' => 'process_name',
                'pattern' => 'kswapd1',
                'severity' => 'critical',
                'description' => 'Fake kernel thread kswapd1 - legitimate systems only have kswapd0.',
                'remediation_hint' => 'Check /proc/PID/exe to find the actual binary and kill it.',
            ],
            [
                'name' => 'Perl Masquerading as acpid',
                'category' => 'botnet',
                'signature_type' => 'process_disguise',
                'pattern' => 'perl.*acpid',
                'severity' => 'critical',
                'description' => 'Perl process pretending to be acpid daemon - IRC botnet indicator.',
                'remediation_hint' => 'Kill the Perl process and check for the bot script location.',
            ],
            [
                'name' => 'Perl Masquerading as sshd',
                'category' => 'botnet',
                'signature_type' => 'process_disguise',
                'pattern' => 'perl.*sshd',
                'severity' => 'critical',
                'description' => 'Perl process pretending to be sshd - IRC botnet indicator.',
                'remediation_hint' => 'Kill the Perl process. The real sshd runs from /usr/sbin/sshd.',
            ],

            // Backdoors
            [
                'name' => '.vbus Hidden Directory',
                'category' => 'backdoor',
                'signature_type' => 'directory_pattern',
                'pattern' => '.vbus',
                'severity' => 'high',
                'description' => 'Known malware staging directory .vbus found in temp locations.',
                'remediation_hint' => 'Remove the directory and all contents. Check for running processes from it.',
            ],
            [
                'name' => 'Triple Dot Directory',
                'category' => 'backdoor',
                'signature_type' => 'directory_pattern',
                'pattern' => '...',
                'severity' => 'high',
                'description' => 'Directory named "..." used to hide malware. Legitimate directories never use this name.',
                'remediation_hint' => 'Remove the directory. It may contain tools, scripts, or IRC bots.',
            ],
            [
                'name' => 'Space-padded Directory',
                'category' => 'backdoor',
                'signature_type' => 'directory_pattern',
                'pattern' => '. ',
                'severity' => 'high',
                'description' => 'Directory with space-padded name used to evade detection.',
                'remediation_hint' => 'Remove the directory using quoted path.',
            ],

            // Proxy Tunnels
            [
                'name' => 'V2Ray Proxy',
                'category' => 'proxy',
                'signature_type' => 'process_name',
                'pattern' => 'v2ray',
                'severity' => 'high',
                'description' => 'V2Ray proxy service detected. Unless intentionally installed, indicates unauthorized proxy.',
                'remediation_hint' => 'Stop and disable the v2ray systemd service.',
            ],
            [
                'name' => 'Shadowsocks Proxy',
                'category' => 'proxy',
                'signature_type' => 'process_name',
                'pattern' => 'shadowsocks',
                'severity' => 'high',
                'description' => 'Shadowsocks proxy detected. Common for unauthorized proxy relay.',
                'remediation_hint' => 'Stop and disable shadowsocks service.',
            ],
            [
                'name' => 'XRay Proxy',
                'category' => 'proxy',
                'signature_type' => 'process_name',
                'pattern' => 'xray',
                'severity' => 'high',
                'description' => 'XRay proxy service detected.',
                'remediation_hint' => 'Stop and disable the xray systemd service.',
            ],

            // Persistence Mechanisms
            [
                'name' => 'Wget Pipe to Shell',
                'category' => 'persistence',
                'signature_type' => 'cron_pattern',
                'pattern' => 'wget.*|.*sh',
                'severity' => 'high',
                'description' => 'Crontab entry downloading and executing scripts. Classic malware persistence.',
                'remediation_hint' => 'Remove the crontab entry and investigate what was being downloaded.',
            ],
            [
                'name' => 'Curl Pipe to Bash',
                'category' => 'persistence',
                'signature_type' => 'cron_pattern',
                'pattern' => 'curl.*|.*bash',
                'severity' => 'high',
                'description' => 'Crontab entry using curl to download and execute scripts.',
                'remediation_hint' => 'Remove the crontab entry and block the source URL.',
            ],
            [
                'name' => 'Base64 Decode Execution',
                'category' => 'persistence',
                'signature_type' => 'cron_pattern',
                'pattern' => 'base64.*-d',
                'severity' => 'high',
                'description' => 'Base64-encoded commands in crontab - obfuscated malware.',
                'remediation_hint' => 'Decode and analyze the command, then remove the crontab entry.',
            ],

            // Malicious Services
            [
                'name' => 'Service from /tmp',
                'category' => 'malicious_service',
                'signature_type' => 'service_path',
                'pattern' => '/tmp/',
                'severity' => 'critical',
                'description' => 'Systemd service running from /tmp directory. No legitimate service runs from /tmp.',
                'remediation_hint' => 'Stop, disable, and remove the service unit file.',
            ],
            [
                'name' => 'Service from /var/tmp',
                'category' => 'malicious_service',
                'signature_type' => 'service_path',
                'pattern' => '/var/tmp/',
                'severity' => 'critical',
                'description' => 'Systemd service running from /var/tmp directory.',
                'remediation_hint' => 'Stop, disable, and remove the service unit file.',
            ],
            [
                'name' => 'Service from /dev/shm',
                'category' => 'malicious_service',
                'signature_type' => 'service_path',
                'pattern' => '/dev/shm/',
                'severity' => 'critical',
                'description' => 'Systemd service running from shared memory. Used by malware to avoid disk writes.',
                'remediation_hint' => 'Stop, disable, and remove the service. Binary only exists in RAM.',
            ],
        ];

        foreach ($signatures as $signature) {
            KnownThreatSignature::updateOrCreate(
                ['name' => $signature['name'], 'category' => $signature['category']],
                $signature
            );
        }
    }
}
