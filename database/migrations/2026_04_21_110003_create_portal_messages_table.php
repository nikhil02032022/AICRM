<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-SP-004 — Portal chat messages between applicant and assigned counsellor
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_messages', function (Blueprint $table): void {
            $table->id();

            $table->string('lead_uuid', 36);
            $table->unsignedBigInteger('institution_id');

            // INBOUND = sent by applicant, OUTBOUND = sent by counsellor
            $table->string('direction', 10);

            // Encrypted at rest — may contain PII
            $table->text('body');

            // Populated for OUTBOUND messages (the counsellor's user ID)
            $table->unsignedBigInteger('sent_by_user_id')->nullable();

            // Set when the applicant opens the chat thread (marks OUTBOUND messages read)
            $table->timestamp('applicant_read_at')->nullable();

            $table->timestamps();

            $table->index('lead_uuid');
            $table->index(['lead_uuid', 'institution_id', 'direction', 'applicant_read_at'],
                'portal_messages_unread_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_messages');
    }
};
