<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareDNSService
{
    private const API_BASE_URL = 'https://api.cloudflare.com/client/v4';

    /**
     * Create a DNS record in a Cloudflare zone.
     *
     * @param string $zoneId
     * @param string $type
     * @param string $name
     * @param string $content
     * @param bool $proxied
     * @return array<string, mixed>
     *
     * @throws \RuntimeException
     */
    public function createDNSRecord(string $zoneId, string $type, string $name, string $content, bool $proxied = false): array
    {
        $response = $this->makeRequest('POST', "/zones/{$zoneId}/dns_records", [
            'type' => $type,
            'name' => $name,
            'content' => $content,
            'proxied' => $proxied,
            'ttl' => $proxied ? 1 : 3600,
        ]);

        Log::info('Cloudflare DNS record created', [
            'zone_id' => $zoneId,
            'type' => $type,
            'name' => $name,
            'record_id' => $response['result']['id'] ?? null,
        ]);

        return $response;
    }

    /**
     * Update an existing DNS record.
     *
     * @param string $zoneId
     * @param string $recordId
     * @param string $content
     * @return array<string, mixed>
     *
     * @throws \RuntimeException
     */
    public function updateDNSRecord(string $zoneId, string $recordId, string $content): array
    {
        $response = $this->makeRequest('PATCH', "/zones/{$zoneId}/dns_records/{$recordId}", [
            'content' => $content,
        ]);

        Log::info('Cloudflare DNS record updated', [
            'zone_id' => $zoneId,
            'record_id' => $recordId,
        ]);

        return $response;
    }

    /**
     * Delete a DNS record.
     *
     * @param string $zoneId
     * @param string $recordId
     * @return bool
     *
     * @throws \RuntimeException
     */
    public function deleteDNSRecord(string $zoneId, string $recordId): bool
    {
        $this->makeRequest('DELETE', "/zones/{$zoneId}/dns_records/{$recordId}");

        Log::info('Cloudflare DNS record deleted', [
            'zone_id' => $zoneId,
            'record_id' => $recordId,
        ]);

        return true;
    }

    /**
     * List DNS records for a zone, optionally filtered by name.
     *
     * @param string $zoneId
     * @param string|null $name
     * @return array<int, mixed>
     *
     * @throws \RuntimeException
     */
    public function listDNSRecords(string $zoneId, ?string $name = null): array
    {
        $query = [];
        if ($name !== null) {
            $query['name'] = $name;
        }

        $response = $this->makeRequest('GET', "/zones/{$zoneId}/dns_records", $query);

        return $response['result'] ?? [];
    }

    /**
     * Make an authenticated request to the Cloudflare API.
     *
     * @param string $method
     * @param string $endpoint
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     *
     * @throws \RuntimeException
     */
    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $token = config('services.cloudflare.api_token');

        if (empty($token)) {
            throw new \RuntimeException('Cloudflare API token is not configured. Set CLOUDFLARE_API_TOKEN in .env');
        }

        $url = self::API_BASE_URL . $endpoint;

        /** @var Response $response */
        $response = match (strtoupper($method)) {
            'GET' => Http::withToken($token)->get($url, $data),
            'POST' => Http::withToken($token)->post($url, $data),
            'PATCH' => Http::withToken($token)->patch($url, $data),
            'DELETE' => Http::withToken($token)->delete($url, $data),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };

        if ($response->failed()) {
            $body = $response->json();
            $errors = $body['errors'] ?? [];
            $errorMessage = ! empty($errors)
                ? collect($errors)->pluck('message')->implode(', ')
                : $response->body();

            Log::error('Cloudflare API request failed', [
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'errors' => $errors,
            ]);

            throw new \RuntimeException("Cloudflare API error: {$errorMessage}");
        }

        /** @var array<string, mixed> */
        return $response->json();
    }
}
