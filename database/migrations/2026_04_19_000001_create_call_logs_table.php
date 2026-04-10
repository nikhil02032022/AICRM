<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-CC-018 — Calls logged automatically with duration, disposition and recording (consented)
// BRD: CRM-CC-016 — Cloud telephony integration (Exotel, Ozonetel, Knowlarity)
// BRD: CRM-LC-010 — Inbound IVR calls auto-create leads
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete(); // null if IVR lead not yet created
            $table->string('telephony_provider', 20); // TelephonyProvider enum: EXOTEL, OZONETEL, KNOWLARITY
            $table->string('provider_call_id')->nullable(); // provider-assigned call SID for dedup
            $table->string('direction', 10); // CallDirection enum: INBOUND, OUTBOUND
            $table->text('from_number'); // encrypted — DPDP
            $table->text('to_number'); // encrypted — DPDP
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->string('disposition', 30)->nullable(); // CallDisposition enum
            $table->text('disposition_notes')->nullable();
            $table->boolean('call_consent_given')->default(false); // BRD: CRM-CR-004 — DPDP
            $table->string('recording_url')->nullable(); // S3 URL — only if consent given
            $table->string('status', 20)->default('INITIATED'); // CallStatus enum
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete(); // null for inbound IVR
            $table->timestamp('called_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index('lead_id');
            $table->index('direction');
            $table->index('status');
            $table->index('provider_call_id');
            $table->index(['institution_id', 'lead_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};
