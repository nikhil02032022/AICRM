<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consent records table — DPDP Act 2023 compliance.
 *
 * BRD: CRM-CR-001 — Explicit consent at point of lead creation.
 * BRD: CRM-CR-002 — Consent stored with timestamp, IP, form version.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id')->nullable()->index(); // null before lead is saved
            $table->unsignedBigInteger('institution_id');
            $table->boolean('consent_given');
            $table->timestamp('consent_timestamp');
            $table->string('consent_ip', 45);
            $table->string('consent_form_version', 20);
            $table->string('consent_channel', 50)->default('web_form'); // web_form|api|import|telephony
            $table->text('consent_text_snapshot')->nullable(); // snapshot of consent wording
            $table->timestamps();

            $table->index(['institution_id', 'consent_timestamp']);
            $table->index(['lead_id', 'consent_given']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_records');
    }
};
