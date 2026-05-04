<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_logs', function (Blueprint $table) {
            $table->id();
            // Nullable: if the endpoint was not found, we still log the attempt
            $table->foreignId('endpoint_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 10);
            $table->string('path');
            // Incoming request: {headers, body, query}
            $table->jsonb('request_data')->nullable();
            // Outgoing response: {status, headers, body}
            $table->jsonb('response_data')->nullable();
            $table->enum('mode_used', ['mock', 'proxy', 'not_found'])->default('not_found');
            $table->unsignedInteger('duration_ms')->default(0);
            // No updated_at — logs are append-only
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_logs');
    }
};
