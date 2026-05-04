<?php

namespace App\Http\Resources;

use App\Models\RequestLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RequestLog */
class RequestLogResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'endpoint_id' => $this->endpoint_id,
            'endpoint' => new EndpointResource($this->whenLoaded('endpoint')),
            'method' => $this->method,
            'path' => $this->path,
            'request_data' => $this->request_data,
            'response_data' => $this->response_data,
            'mode_used' => $this->mode_used,
            'duration_ms' => $this->duration_ms,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
