<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // URL-safe identifier used in gateway route: /gateway/{slug}/...
            $table->string('slug')->unique();
            // Base URL for proxy mode (e.g. https://api.real-bank.local)
            $table->string('base_url')->nullable();
            $table->text('description')->nullable();
            // Default mode for all endpoints of this service
            $table->enum('mode', ['mock', 'proxy'])->default('mock');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
