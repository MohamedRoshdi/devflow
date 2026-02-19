<?php

declare(strict_types=1);

namespace App\Enums;

enum RoutingType: string
{
    case Latency = 'latency';
    case Geolocation = 'geolocation';
    case Weighted = 'weighted';
    case Failover = 'failover';

    /**
     * Get all possible values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get display label for the routing type
     */
    public function label(): string
    {
        return match ($this) {
            self::Latency => 'Latency-Based',
            self::Geolocation => 'Geolocation',
            self::Weighted => 'Weighted',
            self::Failover => 'Failover',
        };
    }

    /**
     * Get description for the routing type
     */
    public function description(): string
    {
        return match ($this) {
            self::Latency => 'Routes traffic to the region with the lowest latency for the user.',
            self::Geolocation => 'Routes traffic based on the geographic location of the user.',
            self::Weighted => 'Distributes traffic across regions according to assigned weights.',
            self::Failover => 'Routes traffic to a primary region and fails over to secondary regions on failure.',
        };
    }
}
