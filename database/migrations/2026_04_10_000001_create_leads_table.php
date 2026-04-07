<?php

declare(strict_types=1);

use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Enums\CRM\LeadTemperature;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-LC-011 — Counsellors must be able to manually create leads
// BRD: CRM-LC-014 — Every lead must carry a mandatory Source field
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            // Multi-tenancy — BRD NFR-MT-001
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();

            // Core identity — PII encrypted at rest (app/Models/CRM/Lead.php casts)
            $table->string('first_name');
            $table->string('last_name');
            $table->text('mobile');        // encrypted
            $table->text('email')->nullable(); // encrypted

            // BRD: CRM-LC-014 — mandatory source field
            $table->string('source')->default(LeadSource::WALK_IN->value);
            $table->json('source_utm_params')->nullable(); // UTM for paid channels

            // Scoring & temperature — BRD: CRM-LQ-001
            $table->unsignedTinyInteger('lead_score')->default(0);
            $table->string('temperature')->default(LeadTemperature::COLD->value);

            // Pipeline status — BRD: CRM-LC-001
            $table->string('status')->default(LeadStatus::NEW_ENQUIRY->value);

            // Assignment — BRD: CRM-EC-001 (counsellor assignment)
            $table->unsignedBigInteger('assigned_counsellor_id')->nullable();

            // Agent/Channel Partner — BRD: CRM-AG-001
            $table->unsignedBigInteger('agent_id')->nullable();

            // DPDP Act 2023 — CRM-CR-001 consent fields (mandatory)
            $table->boolean('consent_given')->default(false);
            $table->timestamp('consent_timestamp')->nullable();
            $table->string('consent_ip', 45)->nullable();
            $table->string('consent_form_version', 30)->nullable();

            // Communication opt-out — BRD: CRM-CR-004
            $table->boolean('opt_out')->default(false);
            $table->timestamp('opt_out_at')->nullable();

            // Call recording consent — BRD: CRM-CR-007
            $table->boolean('call_consent_given')->default(false);

            // ERP bridge — set when lead converts to Student Master — BRD: CRM-EI-001
            $table->uuid('erp_student_uuid')->nullable();

            // DPDP Right to Erasure — BRD: CRM-CR-003
            $table->timestamp('pii_anonymised_at')->nullable();

            // Additional profile fields
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('nationality', 60)->nullable()->default('Indian');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes required for performance — BRD NFR-PE-001 (<= 500ms at P95)
            $table->index('institution_id');
            $table->index('campus_id');
            $table->index('status');
            $table->index('temperature');
            $table->index('lead_score');
            $table->index('assigned_counsellor_id');
            $table->index('agent_id');
            $table->index('source');
            $table->index('created_at');
            $table->index('opt_out');
            $table->index(['institution_id', 'status']);
            $table->index(['institution_id', 'temperature']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
