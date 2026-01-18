<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServerMetricResource extends JsonResource
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
            'cpu_usage' => $this->cpu_usage,
            'memory_usage' => $this->memory_usage,
            'disk_usage' => $this->disk_usage,
            'network_in' => $this->network_in,
            'network_out' => $this->network_out,
            'load_average' => $this->load_average,
            'uptime' => $this->uptime,
            'recorded_at' => $this->created_at->toISOString(),
        ];
    }
}
