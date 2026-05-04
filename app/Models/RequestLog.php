<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestLog extends Model
{
    // Logs are append-only — no updates needed
    public $timestamps = false;

    protected $fillable = [
        'endpoint_id',
        'method',
        'path',
        'request_data',
        'response_data',
        'mode_used',
        'duration_ms',
        'created_at',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'duration_ms' => 'integer',
        'created_at' => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    /** @return BelongsTo<Endpoint, $this> */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }
}
