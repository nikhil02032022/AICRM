<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-CC-010, CRM-CC-012 — WhatsApp shared inbox (BSP conversation-level entity)
// BRD: CRM-LC-007 — Auto-created from inbound WhatsApp click-to-chat
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_conversations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete(); // null if not yet matched
            $table->string('bsp_conversation_id')->nullable(); // BSP-assigned conversation ID
            $table->text('wa_phone_number'); // encrypted — contact's WhatsApp number (DPDP)
            $table->text('wa_display_name')->nullable(); // encrypted — contact's WA profile name (DPDP)
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('OPEN'); // ConversationStatus enum
            $table->timestamp('last_message_at')->nullable();
            $table->boolean('is_bot_active')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index('lead_id');
            $table->index('status');
            $table->index('bsp_conversation_id');
            $table->index(['institution_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversations');
    }
};
