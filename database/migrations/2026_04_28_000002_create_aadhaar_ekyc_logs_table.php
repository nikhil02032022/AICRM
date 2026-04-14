<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-DM-007 — Aadhaar eKYC via API Setu OTP-based verification — audit log per attempt
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aadhaar_ekyc_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->unsignedBigInteger('lead_id');
            $table->string('status', 30)->default('initiated');   // AadhaarKycStatus enum
            // API Setu transaction reference — NOT storing Aadhaar number (DPDP)
            $table->string('transaction_id', 120)->nullable();
            // OTP reference token (short-lived, not the OTP itself)
            $table->string('otp_reference', 120)->nullable();
            // KYC result: name match flag only — no PII fields persisted
            $table->boolean('name_match')->nullable();
            $table->boolean('kyc_complete')->default(false);
            $table->timestamp('kyc_completed_at')->nullable();
            // Consent tracking (DPDP: Aadhaar OTP constitutes explicit consent)
            $table->string('consent_ip', 45)->nullable();
            $table->timestamp('consent_at')->nullable();
            // Error tracking
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index(['institution_id', 'lead_id']);
            $table->index(['institution_id', 'status']);
            $table->index('lead_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aadhaar_ekyc_logs');
    }
};
