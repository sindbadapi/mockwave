<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // URL that will receive the simulated webhook POST/PUT/etc.
            $table->string('target_url');
            $table->string('method')->default('POST');
            // JSON payload to send with the webhook
            $table->jsonb('payload')->nullable();
            // Additional headers to include in the outgoing request
            $table->jsonb('headers')->nullable();
            // Standard cron expression, e.g. "*/15 * * * *"
            $table->string('cron_expression');
            $table->boolean('is_active')->default(true);
            // Timestamp of last successful dispatch (for monitoring)
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_webhooks');
    }
};
