<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GPSService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('app.gps_api_key');
    }

    public function discoverProjects(float $latitude, float $longitude, float $radiusKm = 10): array
    {
        // Search for projects within radius
        $projects = \App\Models\Project::selectRaw("
                *,
                (6371 * acos(
                    cos(radians(?)) *
                    cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) *
                    sin(radians(latitude))
                )) AS distance
            ", [$latitude, $longitude, $latitude])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance')
            ->get();

        return $projects->map(function($project) {
            return [
                'id' => $project->id,
                'name' => $project->name,
                'latitude' => $project->latitude,
                'longitude' => $project->longitude,
                'distance' => round($project->distance, 2),
                'status' => $project->status,
            ];
        })->toArray();
    }

    public function discoverServers(float $latitude, float $longitude, float $radiusKm = 10): array
    {
        // Search for servers within radius
        $servers = \App\Models\Server::selectRaw("
                *,
                (6371 * acos(
                    cos(radians(?)) *
                    cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) *
                    sin(radians(latitude))
                )) AS distance
            ", [$latitude, $longitude, $latitude])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance')
            ->get();

        return $servers->map(function($server) {
            return [
                'id' => $server->id,
                'name' => $server->name,
                'latitude' => $server->latitude,
                'longitude' => $server->longitude,
                'distance' => round($server->distance, 2),
                'status' => $server->status,
            ];
        })->toArray();
    }

    public function getLocationName(float $latitude, float $longitude): ?string
    {
        if (!$this->apiKey) {
            return null;
        }

        try {
            // Using OpenStreetMap Nominatim (free) for reverse geocoding
            $response = Http::get('https://nominatim.openstreetmap.org/reverse', [
                'lat' => $latitude,
                'lon' => $longitude,
                'format' => 'json',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $address = $data['address'] ?? [];
                
                $parts = [];
                if (isset($address['city'])) $parts[] = $address['city'];
                if (isset($address['state'])) $parts[] = $address['state'];
                if (isset($address['country'])) $parts[] = $address['country'];
                
                return implode(', ', $parts);
            }
        } catch (\Exception $e) {
            \Log::error('Reverse geocoding failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    public function calculateDistance(
        float $lat1, 
        float $lon1, 
        float $lat2, 
        float $lon2
    ): float {
        // Haversine formula
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;

        return round($distance, 2);
    }
}

