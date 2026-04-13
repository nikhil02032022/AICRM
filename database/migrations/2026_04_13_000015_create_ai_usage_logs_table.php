<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AI-012 — Persist AI usage logs for auditability and DPDP compliance review
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->unsignedBigInteger('lead_id')->nullable()->index();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('feature_key', 80);
            $table->string('action', 60);
            $table->string('event_name', 140);
            $table->uuid('reference_uuid')->nullable()->index();
            $table->json('context')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index('institution_id');
            $table->index('feature_key');
            $table->index('action');
            $table->index('occurred_at');
            $table->index(['institution_id', 'occurred_at'], 'ai_usage_inst_occurred_idx');

            $table->foreign('lead_id')
                ->references('id')
                ->on('leads')
                ->nullOnDelete();

            $table->foreign('actor_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
