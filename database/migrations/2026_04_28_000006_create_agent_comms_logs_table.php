<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AG-008 — Agent bulk communication log — email/WhatsApp/SMS blasts to agent network
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_comms_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->unsignedBigInteger('sent_by');                   // admin user ID
            $table->string('channel', 20);                           // AgentCommsChannel enum: email|whatsapp|sms
            $table->string('subject', 255)->nullable();              // email subject
            $table->text('message_body');
            // Recipients snapshot — agent user IDs (no PII stored here, DPDP)
            $table->json('recipient_agent_ids');                     // array of agent user IDs
            $table->unsignedInteger('recipient_count')->default(0);
            // Delivery tracking
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->string('status', 30)->default('queued');         // queued|sending|sent|failed
            $table->timestamp('sent_at')->nullable();
            // Opt-out compliance (DPDP: agents can opt out of bulk comms)
            $table->boolean('opt_out_respected')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index(['institution_id', 'channel']);
            $table->index(['institution_id', 'status']);
            $table->index('sent_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_comms_logs');
    }
};
