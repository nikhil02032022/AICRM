<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AP-012, CRM-AP-013, CRM-AP-014, CRM-AP-015 — Offer letter lifecycle and acceptance
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_letters', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            // Multi-tenancy
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();

            // Relationships
            $table->uuid('application_uuid');
            $table->uuid('lead_uuid');
            $table->uuid('programme_uuid');

            // Offer lifecycle
            $table->string('status', 30)->default('pending');
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('sent_via', 30)->nullable(); // email, sms, whatsapp

            // Acceptance tracking (DPDP: capture IP and timestamp)
            $table->timestamp('acceptance_recorded_at')->nullable();
            $table->string('acceptance_ip', 45)->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->text('decline_reason')->nullable();

            // Expiration for offer validity
            $table->timestamp('expires_at')->nullable();

            // Document storage (encrypted S3 path)
            $table->text('pdf_path')->nullable();

            // Soft deletes & timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes for filtering and performance
            $table->index('institution_id');
            $table->index('campus_id');
            $table->index('application_uuid');
            $table->index('lead_uuid');
            $table->index('programme_uuid');
            $table->index('status');
            $table->index('generated_at');
            $table->index('expires_at');
            $table->index('created_at');
            $table->index(['institution_id', 'status']);
            $table->index(['institution_id', 'application_uuid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_letters');
    }
};
