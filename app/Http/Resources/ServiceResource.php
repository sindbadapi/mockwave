<?php

namespace App\Http\Resources;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Service */
class ServiceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'base_url' => $this->base_url,
            'gateway_prefix' => $this->gatewayPathPrefix(),
            'gateway_base_uri' => $this->gatewayBaseUri(),
            'gateway_client_hint' => 'Use gateway_base_uri as Guzzle base_uri and send endpoint paths without a leading slash.',
            'description' => $this->description,
            'mode' => $this->mode,
            'is_active' => $this->is_active,
            'endpoints_count' => $this->whenCounted('endpoints'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
