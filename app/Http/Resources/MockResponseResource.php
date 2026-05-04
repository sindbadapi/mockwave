<?php

namespace App\Http\Resources;

use App\Models\MockResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MockResponse */
class MockResponseResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'endpoint_id' => $this->endpoint_id,
            'status_code' => $this->status_code,
            'body' => $this->body,
            'headers' => $this->headers,
            'delay_ms' => $this->delay_ms,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
