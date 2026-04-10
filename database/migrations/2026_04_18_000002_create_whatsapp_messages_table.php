<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-CC-014 — WhatsApp message delivery, read events tracked per message
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('conversation_id')->constrained('whatsapp_conversations')->cascadeOnDelete();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('bsp_message_id')->nullable(); // BSP-assigned message ID for dedup and tracking
            $table->string('direction', 10); // MessageDirection enum: INBOUND, OUTBOUND
            $table->string('message_type', 20)->default('TEXT'); // WaMessageType enum
            $table->text('body')->nullable(); // encrypted for PII content (DPDP)
            $table->string('template_name')->nullable(); // if sent via template
            $table->string('media_url')->nullable(); // S3 URL for media messages
            $table->string('status', 20)->default('PENDING'); // MessageStatus enum
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete(); // null for inbound/bot
            $table->timestamps();
            // No softDeletes — message log is immutable

            $table->index('conversation_id');
            $table->index('bsp_message_id');
            $table->index('direction');
            $table->index('status');
            $table->index(['conversation_id', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
