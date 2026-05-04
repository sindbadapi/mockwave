<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mock_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('endpoint_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('status_code')->default(200);
            // Response body — stored as JSONB for indexed queries; use jsonb cast in model
            $table->jsonb('body')->nullable();
            // Custom response headers, e.g. {"Content-Type": "application/json"}
            $table->jsonb('headers')->nullable();
            // Artificial delay before responding (milliseconds)
            $table->unsignedInteger('delay_ms')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mock_responses');
    }
};
