<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            // HTTP method (ANY = match all methods)
            $table->string('method')->default('ANY');
            // Path relative to service base, e.g. /v1/accounts or /v1/accounts/*
            $table->string('path');
            // Overrides the service-level mode for this specific endpoint.
            // NULL = inherit from service.
            $table->enum('mode_override', ['mock', 'proxy'])->nullable();
            // Used only when mode resolves to 'proxy'.
            // If null, falls back to service base_url + path.
            $table->string('proxy_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Composite unique: one entry per method+path per service
            $table->unique(['service_id', 'method', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('endpoints');
    }
};
