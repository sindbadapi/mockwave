<?php

namespace App\Models;

use Database\Factories\ScheduledWebhookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledWebhook extends Model
{
    /** @use HasFactory<ScheduledWebhookFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'target_url',
        'method',
        'payload',
        'headers',
        'cron_expression',
        'is_active',
        'last_run_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
    ];
}
