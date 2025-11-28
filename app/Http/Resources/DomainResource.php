<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DomainResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'domain' => $this->domain,
            'subdomain' => $this->subdomain,
            'full_domain' => $this->full_domain,
            'is_primary' => $this->is_primary,
            'ssl_enabled' => $this->ssl_enabled,
            'ssl_expires_at' => $this->ssl_expires_at?->toISOString(),
            'status' => $this->status,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
