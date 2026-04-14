<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-SA-011 — System health monitoring: periodic probe snapshots
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_health_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('component', 50);        // SystemHealthComponent enum
            $table->string('status', 20);           // SystemHealthStatus enum
            $table->string('metric_name', 100);     // e.g. queue_depth, latency_ms, error_rate
            $table->decimal('metric_value', 12, 4)->nullable();
            $table->json('metadata')->nullable();   // extra probe details (no PII)
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['component', 'recorded_at']);
            $table->index(['status', 'recorded_at']);
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_health_logs');
    }
};
