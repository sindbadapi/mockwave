<?php

namespace App\Http\Resources;

use App\Models\Endpoint;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Endpoint */
class EndpointResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_id' => $this->service_id,
            'service' => new ServiceResource($this->whenLoaded('service')),
            'method' => $this->method,
            'path' => $this->path,
            'mode_override' => $this->mode_override,
            'resolved_mode' => $this->resolvedMode(),
            'proxy_url' => $this->proxy_url,
            'is_active' => $this->is_active,
            'mock_response' => new MockResponseResource($this->whenLoaded('mockResponse')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
