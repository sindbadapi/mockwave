<?php

namespace App\Models;

use Database\Factories\EndpointFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Endpoint extends Model
{
    /** @use HasFactory<EndpointFactory> */
    use HasFactory;

    protected $fillable = [
        'service_id',
        'method',
        'path',
        'mode_override',
        'proxy_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    /** @return BelongsTo<Service, $this> */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /** @return HasOne<MockResponse, $this> */
    public function mockResponse(): HasOne
    {
        return $this->hasOne(MockResponse::class);
    }

    /** @return HasMany<RequestLog, $this> */
    public function requestLogs(): HasMany
    {
        return $this->hasMany(RequestLog::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Resolved mode: endpoint override takes priority over service default.
     */
    public function resolvedMode(): string
    {
        return $this->mode_override ?? $this->service->mode;
    }
}
