<?php

declare(strict_types=1);

namespace App\Services\Security;

/**
 * Service for sanitizing and validating SSH commands to prevent
 * command injection and dangerous operations.
 */
class CommandSanitizationService
{
    /**
     * Commands that are completely blocked (dangerous/destructive)
     *
     * @var array<int, string>
     */
    private const BLOCKED_COMMANDS = [
        'rm -rf /',
        'rm -rf /*',
        'rm -rf ~',
        'rm -rf ~/*',
        'rm -rf .',
        'rm -rf ./*',
        'mkfs',
        'mkfs.ext4',
        'mkfs.ext3',
        'mkfs.xfs',
        'dd if=/dev/zero',
        'dd if=/dev/random',
        'dd if=/dev/urandom',
        ':(){ :|:& };:',  // Fork bomb
        'chmod -R 777 /',
        'chmod -R 000 /',
        'chown -R',
        'shutdown',
        'reboot',
        'init 0',
        'init 6',
        'halt',
        'poweroff',
        'telinit 0',
        '> /dev/sda',
        '> /dev/sdb',
        'cat /dev/zero',
    ];

    /**
     * Dangerous patterns that indicate potential command injection
     *
     * @var array<int, string>
     */
    private const DANGEROUS_PATTERNS = [
        '/^wget\s+https?:\/\//i',  // wget with URL
        '/^curl\s+.*-[oO]\s+/i',  // curl with output file
        '/^curl\s+.*--output\s+/i',  // curl with --output
        '/;\s*rm\s+-rf/i',
        '/\|\s*rm\s+-rf/i',
        '/&&\s*rm\s+-rf/i',
        '/`[^`]*rm[^`]*`/',  // Backtick execution with rm
        '/\$\([^)]*rm[^)]*\)/',  // Subshell execution with rm
        '/>\s*\/dev\/sd[a-z]/i',  // Writing to disk devices
        '/>\s*\/dev\/null/i',  // Allowed but logged
        '/mkfs\./i',
        '/dd\s+if=/i',
        '/>\s*\/etc\//i',  // Writing to /etc
        '/>\s*\/boot\//i',  // Writing to /boot
        '/>\s*\/usr\//i',  // Writing to /usr
        '/>\s*\/bin\//i',  // Writing to /bin
        '/>\s*\/sbin\//i',  // Writing to /sbin
        '/base64\s+-d.*\|.*sh/i',  // Base64 encoded command execution
        '/base64\s+--decode.*\|.*sh/i',
        '/echo\s+.*\|.*base64.*\|.*sh/i',
        '/python.*-c.*import.*os/i',  // Python shell escape
        '/perl.*-e.*system/i',  // Perl shell escape
        '/ruby.*-e.*exec/i',  // Ruby shell escape
        '/\beval\b.*\$/i',  // Eval with variable
        '/\bexec\b.*\$/i',  // Exec with variable
    ];

    /**
     * Commands that require elevated caution (allowed but logged as warning)
     *
     * @var array<int, string>
     */
    private const CAUTIOUS_COMMANDS = [
        'rm -rf',
        'rm -r',
        'sudo rm',
        'chmod 777',
        'chmod 000',
        'kill -9',
        'killall',
        'pkill',
        'iptables',
        'ufw',
        'firewall-cmd',
        'systemctl stop',
        'systemctl disable',
        'service stop',
        'apt remove',
        'apt purge',
        'yum remove',
        'dnf remove',
        'docker rm',
        'docker rmi',
        'docker system prune',
    ];

    /**
     * Validate and sanitize a command for SSH execution
     *
     * @return array{valid: bool, sanitized: string, warning: string|null, blocked_reason: string|null}
     */
    public function validateCommand(string $command): array
    {
        $command = trim($command);

        // Empty command check
        if ($command === '') {
            return [
                'valid' => false,
                'sanitized' => '',
                'warning' => null,
                'blocked_reason' => 'Empty command',
            ];
        }

        // Check for blocked commands (exact match or starts with)
        foreach (self::BLOCKED_COMMANDS as $blocked) {
            if ($this->commandMatches($command, $blocked)) {
                return [
                    'valid' => false,
                    'sanitized' => $command,
                    'warning' => null,
                    'blocked_reason' => "Blocked command pattern: {$blocked}",
                ];
            }
        }

        // Check for dangerous patterns
        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $command)) {
                return [
                    'valid' => false,
                    'sanitized' => $command,
                    'warning' => null,
                    'blocked_reason' => 'Potentially dangerous command pattern detected',
                ];
            }
        }

        // Check for cautious commands (allowed but with warning)
        $warning = null;
        foreach (self::CAUTIOUS_COMMANDS as $cautious) {
            if (stripos($command, $cautious) !== false) {
                $warning = "Caution: Command contains '{$cautious}' - ensure this is intentional";
                break;
            }
        }

        // Sanitize: Remove null bytes and control characters
        $sanitized = $this->sanitizeCommand($command);

        return [
            'valid' => true,
            'sanitized' => $sanitized,
            'warning' => $warning,
            'blocked_reason' => null,
        ];
    }

    /**
     * Check if a command matches a blocked pattern
     */
    private function commandMatches(string $command, string $blocked): bool
    {
        $normalizedCommand = strtolower(trim($command));
        $normalizedBlocked = strtolower(trim($blocked));

        // Exact match
        if ($normalizedCommand === $normalizedBlocked) {
            return true;
        }

        // Starts with blocked command
        if (str_starts_with($normalizedCommand, $normalizedBlocked . ' ')) {
            return true;
        }

        // Contains blocked command after semicolon or pipe
        if (preg_match('/[;&|]\s*' . preg_quote($normalizedBlocked, '/') . '/i', $normalizedCommand)) {
            return true;
        }

        return false;
    }

    /**
     * Sanitize command string
     */
    private function sanitizeCommand(string $command): string
    {
        // Remove null bytes
        $command = str_replace("\0", '', $command);

        // Remove other control characters except newline and tab
        $command = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $command) ?? $command;

        // Normalize whitespace
        $command = preg_replace('/\s+/', ' ', $command) ?? $command;

        return trim($command);
    }

    /**
     * Get list of blocked commands for display purposes
     *
     * @return array<int, string>
     */
    public function getBlockedCommands(): array
    {
        return self::BLOCKED_COMMANDS;
    }

    /**
     * Get list of cautious commands for display purposes
     *
     * @return array<int, string>
     */
    public function getCautiousCommands(): array
    {
        return self::CAUTIOUS_COMMANDS;
    }

    /**
     * Check if command is safe for automated execution (stricter rules)
     */
    public function isSafeForAutomation(string $command): bool
    {
        $result = $this->validateCommand($command);

        // For automation, we also reject commands with warnings
        return $result['valid'] && $result['warning'] === null;
    }
}
