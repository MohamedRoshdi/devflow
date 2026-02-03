<?php

declare(strict_types=1);

namespace App\Enums;

enum ThreatCategory: string
{
    case CryptoMiner = 'crypto_miner';
    case Botnet = 'botnet';
    case Backdoor = 'backdoor';
    case Proxy = 'proxy';
    case Rootkit = 'rootkit';
    case MaliciousService = 'malicious_service';
    case ProcessDisguise = 'process_disguise';
    case MiningPool = 'mining_pool';
    case Persistence = 'persistence';
    case BruteForce = 'brute_force';

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
            self::CryptoMiner => 'Crypto Miner',
            self::Botnet => 'IRC Botnet',
            self::Backdoor => 'Backdoor',
            self::Proxy => 'Proxy Tunnel',
            self::Rootkit => 'Rootkit',
            self::MaliciousService => 'Malicious Service',
            self::ProcessDisguise => 'Process Disguise',
            self::MiningPool => 'Mining Pool Connection',
            self::Persistence => 'Persistence Mechanism',
            self::BruteForce => 'Brute Force',
        };
    }

    public function severity(): string
    {
        return match ($this) {
            self::CryptoMiner, self::Botnet, self::Backdoor, self::Rootkit => 'critical',
            self::MaliciousService, self::ProcessDisguise, self::MiningPool, self::Proxy => 'high',
            self::Persistence, self::BruteForce => 'medium',
        };
    }

    public function colorClass(): string
    {
        return match ($this) {
            self::CryptoMiner, self::Botnet, self::Backdoor, self::Rootkit => 'bg-red-500/20 text-red-400 border-red-500/30',
            self::MaliciousService, self::ProcessDisguise, self::MiningPool, self::Proxy => 'bg-orange-500/20 text-orange-400 border-orange-500/30',
            self::Persistence, self::BruteForce => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
        };
    }
}
