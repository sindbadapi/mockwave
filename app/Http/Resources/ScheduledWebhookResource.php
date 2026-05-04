<?php

namespace App\Http\Resources;

use App\Models\ScheduledWebhook;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ScheduledWebhook */
class ScheduledWebhookResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'target_url' => $this->target_url,
            'method' => $this->method,
            'payload' => $this->payload,
            'headers' => $this->headers,
            'cron_expression' => $this->cron_expression,
            'is_active' => $this->is_active,
            'last_run_at' => $this->last_run_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
