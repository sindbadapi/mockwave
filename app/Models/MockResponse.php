<?php

namespace App\Models;

use Database\Factories\MockResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MockResponse extends Model
{
    /** @use HasFactory<MockResponseFactory> */
    use HasFactory;

    protected $fillable = [
        'endpoint_id',
        'status_code',
        'body',
        'headers',
        'delay_ms',
    ];

    protected $casts = [
        // JSONB columns — Eloquent handles encode/decode automatically
        'body' => 'array',
        'headers' => 'array',
        'status_code' => 'integer',
        'delay_ms' => 'integer',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    /** @return BelongsTo<Endpoint, $this> */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }
}
