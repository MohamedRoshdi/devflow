<?php

declare(strict_types=1);

namespace App\Enums;

enum RemediationAction: string
{
    case KillProcess = 'kill_process';
    case DisableService = 'disable_service';
    case RemoveService = 'remove_service';
    case BlockIp = 'block_ip';
    case BlockPort = 'block_port';
    case RemoveBinary = 'remove_binary';
    case RemoveCrontab = 'remove_crontab';
    case RemoveDirectory = 'remove_directory';
    case RemoveUser = 'remove_user';
    case HardenSSH = 'harden_ssh';
    case EnableFirewall = 'enable_firewall';
    case InstallFail2ban = 'install_fail2ban';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::KillProcess => 'Kill Process',
            self::DisableService => 'Disable Service',
            self::RemoveService => 'Remove Service',
            self::BlockIp => 'Block IP Address',
            self::BlockPort => 'Block Outbound Port',
            self::RemoveBinary => 'Remove Binary',
            self::RemoveCrontab => 'Remove Crontab Entry',
            self::RemoveDirectory => 'Remove Directory',
            self::RemoveUser => 'Remove User',
            self::HardenSSH => 'Harden SSH',
            self::EnableFirewall => 'Enable Firewall',
            self::InstallFail2ban => 'Install Fail2ban',
        };
    }

    public function isDestructive(): bool
    {
        return match ($this) {
            self::RemoveService, self::RemoveBinary, self::RemoveDirectory, self::RemoveUser => true,
            default => false,
        };
    }
}
