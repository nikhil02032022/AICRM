<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-CC-003 — Unified communication log (immutable, all channels)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            // Polymorphic — EmailCampaign, SmsCampaign, or null for 1:1
            $table->nullableMorphs('loggable');
            $table->string('channel', 20); // CommunicationChannel enum
            $table->string('direction', 10); // MessageDirection enum: INBOUND, OUTBOUND
            $table->foreignId('template_id')->nullable()->constrained('communication_templates')->nullOnDelete();
            $table->string('subject')->nullable(); // email subject
            $table->string('body_preview', 500)->nullable(); // truncated preview — no PII
            $table->string('status', 20)->default('PENDING'); // MessageStatus enum
            $table->string('external_id')->nullable(); // provider message ID for dedup
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->timestamps();
            // No softDeletes — communication log is immutable

            $table->index('institution_id');
            $table->index('lead_id');
            $table->index('channel');
            $table->index('status');
            $table->index('external_id');
            $table->index(['institution_id', 'lead_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_logs');
    }
};
