<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-FM-005 — Idempotent gateway webhook audit trail
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_webhook_events', function (Blueprint $table): void {
            $table->id();

            $table->string('gateway', 20);
            $table->string('event_id', 191);
            $table->string('event_type', 80)->nullable();

            $table->boolean('signature_valid')->default(false);
            $table->json('payload')->nullable();

            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->string('processing_error', 255)->nullable();

            $table->timestamps();

            $table->unique(['gateway', 'event_id'], 'pwe_gateway_event_unique');
            $table->index('processed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
    }
};
